<?php $__env->startSection('title', '결제하기 — 메디셀'); ?>

<?php ($provider = $order->pay_provider ?? 'toss'); ?>

<?php $__env->startSection('content'); ?>
<div class="container" style="max-width:680px;padding:30px 20px">
    <div class="page-head" style="background:none;color:var(--ink);padding:0 0 18px">
        <h1 style="font-size:24px">결제하기</h1>
    </div>

    <div class="form-card">
        <h3 style="border:0;margin:0 0 10px"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?> 주문 정보</h3>
        <div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0">
            <span class="muted">주문번호</span><b><?php echo e($order->order_no); ?></b>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0">
            <span class="muted">상품</span><span><?php echo e($orderName); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0">
            <span class="muted">결제수단</span><span><?php echo e($provider === 'portone' ? '포트원(아임포트)' : '토스페이먼츠'); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--line);margin-top:10px;padding-top:12px">
            <span>결제금액</span><b class="text-red" style="font-size:22px"><?php echo e(number_format($order->total)); ?>원</b>
        </div>
    </div>

    <?php if($provider === 'portone'): ?>
        
        <div class="form-card">
            <?php if($portone['simulate']): ?>
                <form method="POST" action="<?php echo e(route('payment.portone.simulate', $order)); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-red btn-lg btn-block"><?php echo e(number_format($order->total)); ?>원 결제하기</button>
                </form>
                <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">포트원 시뮬레이트 모드 — 실제 결제창 없이 완료 처리됩니다.</p>
            <?php else: ?>
                <button id="pay-btn" class="btn btn-red btn-lg btn-block"><?php echo e(number_format($order->total)); ?>원 결제하기</button>
                <form id="poVerify" method="POST" action="<?php echo e(route('payment.portone.verify')); ?>" style="display:none">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="imp_uid">
                    <input type="hidden" name="merchant_uid" value="<?php echo e($order->order_no); ?>">
                </form>
                <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">포트원 결제창으로 진행됩니다.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <div class="form-card">
            <div id="payment-method"></div>
            <div id="agreement"></div>
            <button id="pay-btn" class="btn btn-red btn-lg btn-block" style="margin-top:16px" disabled>
                <?php echo e(number_format($order->total)); ?>원 결제하기
            </button>
            <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">테스트 모드입니다. 실제 청구되지 않습니다.</p>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if($provider === 'portone' && ! $portone['simulate']): ?>
<script src="https://cdn.iamport.kr/v1/iamport.js"></script>
<script>
(function () {
    var IMP = window.IMP; IMP.init(<?php echo json_encode($portone['imp_code'], 15, 512) ?>);
    var btn = document.getElementById('pay-btn');
    var form = document.getElementById('poVerify');
    btn.addEventListener('click', function () {
        IMP.request_pay({
            pg: <?php echo json_encode($portone['pg'], 15, 512) ?>,
            pay_method: <?php echo json_encode($portone['pay_method'], 15, 512) ?>,
            merchant_uid: <?php echo json_encode($order->order_no, 15, 512) ?>,
            name: <?php echo json_encode($orderName, 15, 512) ?>,
            amount: <?php echo e((int) $order->total); ?>,
            buyer_name: <?php echo json_encode($order->receiver_name, 15, 512) ?>,
            buyer_tel: <?php echo json_encode($order->receiver_phone, 15, 512) ?>
        }, function (rsp) {
            if (rsp.success || rsp.imp_uid) {
                form.querySelector('[name=imp_uid]').value = rsp.imp_uid;
                form.submit();
            } else {
                alert('결제 실패: ' + (rsp.error_msg || ''));
            }
        });
    });
})();
</script>
<?php elseif($provider !== 'portone'): ?>
<script src="https://js.tosspayments.com/v2/standard"></script>
<script>
(function () {
    var clientKey   = <?php echo json_encode($clientKey, 15, 512) ?>;
    var customerKey = <?php echo json_encode($customerKey, 15, 512) ?>;
    var amount      = <?php echo e((int) $order->total); ?>;
    var payload = {
        orderId:      <?php echo json_encode($order->order_no, 15, 512) ?>,
        orderName:    <?php echo json_encode($orderName, 15, 512) ?>,
        successUrl:   <?php echo json_encode(route('payment.success'), 15, 512) ?>,
        failUrl:      <?php echo json_encode(route('payment.fail'), 15, 512) ?>,
        customerName: <?php echo json_encode($order->receiver_name, 15, 512) ?>
    };
    var btn = document.getElementById('pay-btn');
    var toss = TossPayments(clientKey);
    var widgets = toss.widgets({ customerKey: customerKey });
    (async function () {
        await widgets.setAmount({ currency: 'KRW', value: amount });
        await Promise.all([
            widgets.renderPaymentMethods({ selector: '#payment-method', variantKey: 'DEFAULT' }),
            widgets.renderAgreement({ selector: '#agreement', variantKey: 'AGREEMENT' })
        ]);
        btn.disabled = false;
        btn.addEventListener('click', async function () {
            btn.disabled = true;
            try { await widgets.requestPayment(payload); }
            catch (e) { btn.disabled = false; console.error(e); }
        });
    })();
})();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\medisell\resources\views/order/pay.blade.php ENDPATH**/ ?>