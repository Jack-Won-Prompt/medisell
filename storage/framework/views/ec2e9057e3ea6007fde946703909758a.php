<?php $__env->startSection('title', '비밀번호 찾기 — 메디셀'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-wrap">
    <div class="auth-card">
        <a href="<?php echo e(route('home')); ?>" class="brand" style="justify-content:center"><img src="<?php echo e(asset('images/logo.svg')); ?>" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>비밀번호 찾기</h2>
        <p class="sub">가입하신 이메일로 재설정 링크를 보내드립니다.</p>

        <?php if(session('ok')): ?><div class="alert alert-ok" style="margin-bottom:16px"><?php echo e(session('ok')); ?></div><?php endif; ?>
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="alert alert-red" style="margin-bottom:16px"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <form method="POST" action="<?php echo e(route('password.email')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="<?php echo e(old('email')); ?>" required autofocus placeholder="가입한 이메일 주소">
            </div>
            <button class="btn btn-primary btn-lg btn-block">재설정 링크 받기</button>
        </form>

        <div class="auth-links">
            <a href="<?php echo e(route('login')); ?>">로그인으로 돌아가기</a>
            <span>·</span>
            <a href="<?php echo e(route('register')); ?>">회원가입</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\medisell\resources\views/auth/forgot-password.blade.php ENDPATH**/ ?>