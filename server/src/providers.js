import crypto from 'node:crypto';
import { get, run } from './db.js';

const OS_IMAGES = [
  'Ubuntu 24.04 LTS',
  'Ubuntu 22.04 LTS',
  'Debian 12',
  'AlmaLinux 9',
  'Rocky Linux 9',
  'CentOS Stream 9',
  'Windows Server 2022'
];

export const OS_OPTIONS = OS_IMAGES;

const randomIP = () => `10.${crypto.randomInt(1, 60)}.${crypto.randomInt(1, 255)}.${crypto.randomInt(1, 255)}`;
const randomPass = (len = 12) => {
  const chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  return Array.from({ length: len }, () => chars[crypto.randomInt(chars.length)]).join('');
};

/**
 * Mock 虚拟化 Provider：模拟真实开通耗时，失败率 0（演示环境）。
 * 后续可新增 proxmox.js / solusvm.js，并在 getProvider 中按 node.provider 分发。
 */
const mockProvider = {
  async createServer({ name, os }) {
    return {
      providerId: `kuluoxi-${crypto.randomBytes(3).toString('hex')}`,
      ip: randomIP(),
      rootPassword: randomPass(),
      os
    };
  },
  async power(_providerId, action) {
    return { status: action === 'stop' ? 'stopped' : 'running' };
  },
  async reinstall(_providerId, os) {
    return { os };
  },
  async destroy(_providerId) {
    return { ok: true };
  }
};

export function getProvider(node) {
  switch (node?.provider) {
    case 'proxmox':
    case 'solusvm':
      // 预留实现位：当前统一回落到 mock，避免演示环境依赖真实虚拟化平台
      return mockProvider;
    default:
      return mockProvider;
  }
}

export function completeProvisioning(instanceId) {
  const inst = get('SELECT * FROM instances WHERE id = ?', instanceId);
  if (!inst) return;
  if (inst.status !== 'provisioning') return;
  run("UPDATE instances SET status = 'running', updated_at = ? WHERE id = ?", new Date().toISOString(), instanceId);
  const node = get('SELECT * FROM nodes WHERE id = ?', inst.node_id);
  if (node) {
    run("UPDATE nodes SET used = MIN(capacity, used + 1) WHERE id = ?", node.id);
  }
}

export async function provisionInstance(instanceId) {
  const inst = get('SELECT * FROM instances WHERE id = ?', instanceId);
  if (!inst) return;
  const node = get('SELECT * FROM nodes WHERE id = ?', inst.node_id);
  const provider = getProvider(node);
  const result = await provider.createServer({ name: inst.name, os: inst.os });
  run(
    `UPDATE instances SET provider_id = ?, ip = ?, root_password = ?, status = 'provisioning', updated_at = ?
     WHERE id = ?`,
    result.providerId, result.ip, result.rootPassword, new Date().toISOString(), instanceId
  );
  setTimeout(() => completeProvisioning(instanceId), 6000);
}
