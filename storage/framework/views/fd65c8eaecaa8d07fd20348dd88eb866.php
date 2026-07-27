<?php $__env->startSection('title', '로그인 — 메디셀'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-wrap">
    <div class="auth-card">
        <a href="<?php echo e(route('home')); ?>" class="brand" style="justify-content:center"><img src="<?php echo e(asset('images/logo.svg')); ?>" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>로그인</h2>
        <p class="sub">의료소모품 전문 쇼핑몰 메디셀</p>

        <form method="POST" action="<?php echo e(route('login.attempt')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="<?php echo e(old('email')); ?>" required autofocus>
            </div>
            <div class="field">
                <label>비밀번호</label>
                <input type="password" name="password" class="input" required>
            </div>
            <label class="inline" style="font-size:13px;margin-bottom:16px"><input type="checkbox" name="remember"> 로그인 상태 유지</label>
            <button class="btn btn-primary btn-lg btn-block">로그인</button>
        </form>

        <div class="auth-links">
            <a href="<?php echo e(route('register')); ?>">회원가입</a>
            <span>·</span>
            <a href="<?php echo e(route('password.request')); ?>">비밀번호 찾기</a>
            <span>·</span>
            <a href="<?php echo e(route('community.qna')); ?>">고객센터 문의</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\medisell\resources\views/auth/login.blade.php ENDPATH**/ ?>