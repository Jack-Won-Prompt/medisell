<?php $__env->startSection('title', '주문/결제 — 메디셀'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-head"><div class="container"><h1>주문 / 결제</h1></div></div>

<div class="container" style="padding-top:26px">

<form id="couponForm" method="POST" action="<?php echo e(route('order.coupon.apply')); ?>"><?php echo csrf_field(); ?></form>
<form id="couponRemoveForm" method="POST" action="<?php echo e(route('order.coupon.remove')); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?></form>
<script>
function msApplyCoupon(code){var i=document.querySelector('input[name=code][form=couponForm]')||document.querySelector('input[name=code]');if(i)i.value=code;document.getElementById('couponForm').submit();}
</script>

<form method="POST" action="<?php echo e(route('order.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="cart-layout">
        <div>
            
            <div class="form-card">
                <h3><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'package']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'package']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> 주문상품 <?php echo e($items->count()); ?>건</h3>
                <table class="dtable" style="border:0">
                    <tbody>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($unit = $it->product->priceFor($user)); ?>
                        <tr>
                            <td>
                                <div class="pname">
                                    <span class="pthumb"><?php if($it->product->thumbnail): ?><img src="<?php echo e($it->product->thumbnail); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px" alt=""><?php else: ?><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => $it->product->category->icon ?? 'box']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($it->product->category->icon ?? 'box')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><?php endif; ?></span>
                                    <div><div style="font-weight:600"><?php echo e($it->product->name); ?></div><div class="muted" style="font-size:12px"><?php echo e(number_format($unit)); ?>원 × <?php echo e($it->quantity); ?><?php echo e($it->product->unit); ?></div></div>
                                </div>
                            </td>
                            <td style="text-align:right;width:120px"><b><?php echo e(number_format($unit * $it->quantity)); ?>원</b></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            
            <div class="form-card">
                <h3><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'pin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pin']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> 배송지 정보</h3>
                <div class="row2">
                    <div class="field"><label>받는분 <span class="req">*</span></label><input type="text" name="receiver_name" class="input" value="<?php echo e(old('receiver_name', $user->name)); ?>" required></div>
                    <div class="field"><label>연락처 <span class="req">*</span></label><input type="text" name="receiver_phone" class="input" value="<?php echo e(old('receiver_phone', $user->phone)); ?>" required></div>
                </div>
                <div class="field" style="max-width:200px"><label>우편번호</label><input type="text" name="postcode" class="input" value="<?php echo e(old('postcode', $user->postcode)); ?>"></div>
                <div class="field"><label>주소 <span class="req">*</span></label><input type="text" name="address1" class="input" value="<?php echo e(old('address1', $user->address1)); ?>" placeholder="기본 주소" required></div>
                <div class="field"><label>상세주소</label><input type="text" name="address2" class="input" value="<?php echo e(old('address2', $user->address2)); ?>" placeholder="상세 주소"></div>
                <div class="field"><label>배송 메모</label><input type="text" name="memo" class="input" value="<?php echo e(old('memo')); ?>" placeholder="예) 부재 시 진료실 앞에 놓아주세요"></div>
            </div>

            
            <div class="form-card">
                <h3><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'coin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'coin']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> 결제수단</h3>
                <?php ($pg = $site['payment_pg'] ?? 'toss'); ?>
                <?php ($pgName = $pg === 'portone' ? '포트원(아임포트)' : '토스페이먼츠'); ?>
                <div class="radio-cards" data-radio-cards style="margin-bottom:16px">
                    <label class="radio-card on">
                        <input type="radio" name="payment_method" value="<?php echo e($pg); ?>" hidden checked>
                        <strong>카드 · 가상계좌</strong><small><?php echo e($pgName); ?> (카드/계좌이체/가상계좌)</small>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="payment_method" value="bank" hidden>
                        <strong>무통장입금</strong><small>안내 계좌로 직접 입금</small>
                    </label>
                </div>

                
                <div id="bank-fields" style="display:none">
                    <div class="field">
                        <label>입금하실 은행</label>
                        <select name="bank" class="select">
                            <?php $__currentLoopData = $site['banks']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($b['bank']); ?>"><?php echo e($b['bank']); ?> <?php echo e($b['account']); ?> (<?php echo e($b['holder']); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="field"><label>입금자명</label><input type="text" name="depositor" class="input" value="<?php echo e(old('depositor', $user->name)); ?>"></div>
                </div>
                <p id="toss-hint" class="muted" style="font-size:13px;margin:0">다음 화면에서 토스페이먼츠 결제창을 통해 카드 또는 가상계좌로 결제합니다.</p>
            </div>
        </div>

        
        <?php ($finalTotal = max(0, $summary['total'] - ($couponDiscount ?? 0))); ?>
        <div class="sum-card">
            <h3>결제금액</h3>

            
            <div style="margin-bottom:12px">
                <?php if($coupon): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--navy-50);border:1px solid var(--navy-100);border-radius:8px;padding:9px 12px">
                        <span style="font-size:13px;font-weight:700;color:var(--navy-800)"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'tag','size' => 14]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tag','size' => 14]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> <?php echo e($coupon->name); ?></span>
                        <button type="submit" form="couponRemoveForm" class="btn btn-ghost btn-sm" style="padding:5px 10px">해제</button>
                    </div>
                <?php else: ?>
                    <div style="display:flex;gap:6px">
                        <input type="text" name="code" form="couponForm" class="input" placeholder="쿠폰 코드 입력" style="height:38px">
                        <button type="submit" form="couponForm" class="btn btn-primary btn-sm">적용</button>
                    </div>
                    <?php if($couponError): ?><div class="err" style="font-size:12px;color:var(--red);margin-top:5px"><?php echo e($couponError); ?></div><?php endif; ?>
                    <?php if(isset($availableCoupons) && $availableCoupons->count()): ?>
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                            <span class="muted" style="font-size:12px">보유 쿠폰</span>
                            <?php $__currentLoopData = $availableCoupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" class="chip" style="cursor:pointer;border-color:var(--navy-100)" onclick="msApplyCoupon('<?php echo e($uc->coupon->code); ?>')">
                                    <?php echo e($uc->coupon->name); ?> · <?php echo e($uc->coupon->typeLabel()); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="sum-row"><span>상품금액</span><span><?php echo e(number_format($summary['subtotal'])); ?>원</span></div>
            <div class="sum-row"><span>배송비</span><span><?php echo e($summary['shipping'] ? '+'.number_format($summary['shipping']).'원' : '무료'); ?></span></div>
            <?php if($couponDiscount ?? 0): ?>
                <div class="sum-row" style="color:var(--red)"><span>쿠폰 할인</span><span>-<?php echo e(number_format($couponDiscount)); ?>원</span></div>
            <?php endif; ?>
            <?php if($user->point > 0): ?>
                <div class="sum-row" style="align-items:center">
                    <span>적립금 사용</span>
                    <span class="inline"><input type="number" name="point_used" value="0" min="0" max="<?php echo e(max(0, min($user->point, $summary['subtotal'] - ($couponDiscount ?? 0)))); ?>" class="input" style="width:110px;height:34px;text-align:right">원</span>
                </div>
                <div class="muted" style="font-size:12px;text-align:right">보유 <?php echo e(number_format($user->point)); ?>원</div>
            <?php endif; ?>
            <div class="sum-row total"><span>최종 결제금액</span><b><?php echo e(number_format($finalTotal)); ?>원</b></div>
            <button type="submit" class="btn btn-red btn-lg btn-block" style="margin-top:14px"><?php echo e(number_format($finalTotal)); ?>원 결제하기</button>
            <p class="muted" style="font-size:12px;margin-top:10px">결제수단에 따라 결제창 또는 입금안내로 이동합니다.</p>
        </div>
    </div>
</form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\medisell\resources\views/order/checkout.blade.php ENDPATH**/ ?>