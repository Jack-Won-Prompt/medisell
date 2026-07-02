<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['product']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['product']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $user = auth()->user();
    $sell = $product->priceFor($user);
    $isHospital = $user && $user->isApprovedBusiness();
    $special = $isHospital && $sell < $product->price;     // 병원 전용가(정가보다 낮음)
    $rate = $special ? $product->discountRateFor($sell) : $product->discountRate();
    $soldout = $product->stock <= 0;
    $inWish = in_array($product->id, $wishlistIds ?? []);
?>
<div class="card">
    <?php if(auth()->guard()->check()): ?>
        <form method="POST" action="<?php echo e(route('wishlist.toggle', $product)); ?>" class="wish-form">
            <?php echo csrf_field(); ?>
            <button type="submit" class="wish <?php echo e($inWish ? 'on' : ''); ?>" aria-label="관심상품"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'heart']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'heart']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
        </form>
    <?php else: ?>
        <a href="<?php echo e(route('login')); ?>" class="wish-form wish" aria-label="관심상품(로그인)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'heart']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'heart']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></a>
    <?php endif; ?>
    <a href="<?php echo e(route('catalog.show', $product->slug)); ?>" class="thumb">
        <div class="badges">
            <?php if($special): ?><span class="badge badge-plan">병원가</span><?php endif; ?>
            <?php if($product->is_best): ?><span class="badge badge-best">BEST</span><?php endif; ?>
            <?php if($product->is_new): ?><span class="badge badge-new">NEW</span><?php endif; ?>
            <?php if($product->badge): ?><span class="badge badge-plan"><?php echo e($product->badge); ?></span><?php endif; ?>
            <?php if($soldout): ?><span class="badge badge-soldout">품절</span><?php endif; ?>
        </div>
        <?php if($product->thumbnail): ?>
            <img src="<?php echo e($product->thumbnail); ?>" alt="<?php echo e($product->name); ?>" loading="lazy">
        <?php else: ?>
            <span class="ph"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => $product->category->icon ?? 'box']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($product->category->icon ?? 'box')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span>
        <?php endif; ?>
    </a>
    <div class="info">
        <div class="maker"><?php echo e($product->maker ?? $product->brand?->name); ?></div>
        <a href="<?php echo e(route('catalog.show', $product->slug)); ?>" class="name"><?php echo e($product->name); ?></a>
        <div class="price-row">
            <?php if($special): ?>
                <span class="rate"><?php echo e($rate); ?>%</span>
                <span class="price"><?php echo e(number_format($sell)); ?><span class="won">원</span></span>
                <span class="o-price"><?php echo e(number_format($product->price)); ?>원</span>
            <?php else: ?>
                <span class="price"><?php echo e(number_format($sell)); ?><span class="won">원</span></span>
            <?php endif; ?>
        </div>
        <?php if($special): ?>
            <div><span class="mprice">병원 전용가 적용중</span></div>
        <?php elseif(!$isHospital && ($product->member_price || true)): ?>
            <div><span class="mprice">병원 회원 전용가 별도</span></div>
        <?php endif; ?>
    </div>
    <div class="cart-row">
        <?php if($soldout): ?>
            <button class="btn btn-ghost btn-sm btn-block" disabled>품절</button>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('cart.add', $product)); ?>" style="flex:1">
                <?php echo csrf_field(); ?>
                <button class="btn btn-primary btn-sm btn-block" type="submit"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'cart']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cart']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>담기</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH E:\xampp\htdocs\medisell\resources\views/components/product-card.blade.php ENDPATH**/ ?>