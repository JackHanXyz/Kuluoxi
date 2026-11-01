export const instanceOut = (i, extra = {}) => ({
  id: i.id,
  name: i.name,
  status: i.status,
  providerId: i.provider_id || '',
  ip: i.ip,
  rootPassword: i.root_password,
  os: i.os,
  cpu: i.cpu,
  memoryMb: i.memory_mb,
  diskGb: i.disk_gb,
  bandwidthGb: i.bandwidth_gb,
  trafficUsedGb: Math.round(i.traffic_used_gb * 10) / 10,
  expiresAt: i.expires_at,
  createdAt: i.created_at,
  planName: i.plan_name || extra.planName,
  planCode: i.plan_code || extra.planCode,
  nodeName: i.node_name || extra.nodeName,
  nodeCode: i.node_code || extra.nodeCode,
  region: i.region || extra.region
});

export const orderOut = (o) => ({
  id: o.id,
  orderNo: o.order_no,
  status: o.status,
  months: o.months,
  amount: o.amount,
  paymentMethod: o.payment_method,
  instanceName: o.instance_name,
  os: o.os,
  createdAt: o.created_at,
  paidAt: o.paid_at,
  planName: o.plan_name,
  planCode: o.plan_code,
  nodeName: o.node_name,
  nodeCode: o.node_code
});

export const ticketOut = (t, messages) => ({
  id: t.id,
  subject: t.subject,
  category: t.category,
  priority: t.priority,
  status: t.status,
  createdAt: t.created_at,
  updatedAt: t.updated_at,
  userNickname: t.user_nickname,
  messageCount: t.message_count,
  messages: messages?.map((m) => ({
    id: m.id,
    body: m.body,
    isStaff: !!m.is_staff,
    nickname: m.nickname || '库洛汐支持',
    createdAt: m.created_at
  }))
});
