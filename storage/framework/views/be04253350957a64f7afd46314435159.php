<?php ($__ads = ($sideAds ?? collect())->shuffle()); ?>
<?php if($__ads->isNotEmpty()): ?>
    
    <?php ($__left = $__ads->filter(fn ($a) => in_array($a->position, ['left', 'both']))->take(3)); ?>
    <?php ($__right = $__ads->filter(fn ($a) => in_array($a->position, ['right', 'both']) && ! $__left->contains('id', $a->id))->take(3)); ?>

    <?php $__currentLoopData = ['left' => $__left, 'right' => $__right]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $__side => $__list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($__list->isNotEmpty()): ?>
            <aside class="ad-rail ad-rail-<?php echo e($__side); ?>" aria-label="추천">
                <div class="ad-rail-label">MEDISELL 추천</div>
                <?php $__currentLoopData = $__list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($__banner = $ad->image && ! $ad->price); ?>
                    <a class="ad-card <?php echo e($__banner ? 'ad-card-banner' : ''); ?>"
                       <?php if($ad->link): ?> href="<?php echo e($ad->link); ?>" <?php if(\Illuminate\Support\Str::startsWith($ad->link, ['http']) && ! \Illuminate\Support\Str::contains($ad->link, request()->getHost())): ?> target="_blank" rel="noopener nofollow sponsored" <?php endif; ?> <?php else: ?> href="javascript:void(0)" <?php endif; ?>
                       aria-label="<?php echo e($ad->title); ?>">
                        <?php if($__banner): ?>
                            
                            <span class="ad-banner-img"><img src="<?php echo e($ad->image); ?>" alt="<?php echo e($ad->title); ?>" loading="lazy"></span>
                        <?php else: ?>
                            <?php if($ad->badge): ?><span class="ad-badge"><?php echo e($ad->badge); ?></span><?php endif; ?>
                            <?php if($ad->image): ?>
                                <span class="ad-thumb"><img src="<?php echo e($ad->image); ?>" alt="<?php echo e($ad->title); ?>" loading="lazy"></span>
                            <?php else: ?>
                                <span class="ad-thumb ad-thumb-empty" <?php if($ad->bg_color): ?> style="background:<?php echo e($ad->bg_color); ?>" <?php endif; ?>><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'box','size' => 36]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'box','size' => 36]); ?>
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
                            <span class="ad-body">
                                <span class="ad-title"><?php echo e($ad->title); ?></span>
                                <?php if($ad->subtitle): ?><span class="ad-sub"><?php echo e($ad->subtitle); ?></span><?php endif; ?>
                                <?php if($ad->price): ?><span class="ad-price"><?php echo e(number_format($ad->price)); ?><em>원~</em></span><?php endif; ?>
                                <span class="ad-cta">자세히 보기 →</span>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </aside>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH E:\xampp\htdocs\medisell\resources\views/partials/ad-rails.blade.php ENDPATH**/ ?>