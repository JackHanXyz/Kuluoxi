<div class="notfound">
    <div class="code">404</div>
    <h1 style="font-size:22px;margin-top:8px">页面走丢了</h1>
    <p>你访问的地址不存在，或已被移除。</p>
    <div class="btn-row" style="justify-content:center">
        <a class="btn btn--primary" href="<?= e(url('/')) ?>">返回首页</a>
        <?php if (!empty($user)): ?>
            <a class="btn btn--ghost" href="<?= e(url('/app')) ?>">进入控制台</a>
        <?php endif; ?>
    </div>
</div>
