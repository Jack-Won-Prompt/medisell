<?php $__env->startSection('title', '쿠팡 경쟁가'); ?>
<?php $__env->startSection('heading', '쿠팡 경쟁가 조회'); ?>

<?php $__env->startSection('content'); ?>
<div class="adm-card">
    <div class="h">
        <span>제품 선택 / 키워드 검색 <?php if($simulate): ?><span class="pill pill-w">시뮬레이트</span><?php else: ?><span class="pill pill-y">실연동</span><?php endif; ?></span>
    </div>
    <div style="padding:20px">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="afield" style="margin:0;min-width:320px;flex:1">
                <label>메디셀 제품</label>
                <select name="product_id" class="aselect" onchange="this.form.q.value='';this.form.submit()">
                    <option value="">— 제품 선택 —</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>" <?php echo e(optional($product)->id===$p->id ? 'selected' : ''); ?>><?php echo e($p->name); ?> (<?php echo e(number_format($p->price)); ?>원)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="afield" style="margin:0;min-width:220px">
                <label>또는 직접 키워드</label>
                <input type="text" name="q" class="ainput" value="<?php echo e($product ? '' : $keyword); ?>" placeholder="예: 멸균거즈">
            </div>
            <button class="abtn abtn-pri">쿠팡 조회</button>
        </form>
        <div class="ahint" style="margin-top:8px">
            <?php if($simulate): ?>
                <b>시뮬레이트 모드</b> — 제품명 기반 모의 경쟁가입니다. <b>쿠팡 파트너스 키 발급 대기중.</b><br>
                키 발급 후 <code>.env</code>에 <code>COUPANG_PARTNERS_ACCESS_KEY</code>·<code>COUPANG_PARTNERS_SECRET_KEY</code> 설정 + <code>COUPANG_SIMULATE=false</code> 하면 실제 쿠팡 파트너스 검색가로 전환됩니다.
            <?php else: ?>
                실연동 모드 — 쿠팡 파트너스 검색 API로 타사 판매가를 조회합니다.
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($keyword !== ''): ?>
    <div class="adm-card">
        <div class="h">
            <span>「<?php echo e($keyword); ?>」 쿠팡 검색결과 <span class="pill pill-b"><?php echo e(count($results)); ?>건</span></span>
            <?php if($refPrice): ?><span class="muted" style="font-size:13px">메디셀 판매가 <b style="color:var(--a-navy)"><?php echo e(number_format($refPrice)); ?>원</b></span><?php endif; ?>
        </div>

        <?php if($stats): ?>
        <div class="adm-stats" style="grid-template-columns:repeat(4,1fr);margin:16px 20px">
            <div class="adm-stat"><div><div class="v"><?php echo e(number_format($stats['min'])); ?></div><div class="l">최저가(원)</div></div></div>
            <div class="adm-stat"><div><div class="v"><?php echo e(number_format($stats['avg'])); ?></div><div class="l">평균가(원)</div></div></div>
            <div class="adm-stat"><div><div class="v"><?php echo e(number_format($stats['max'])); ?></div><div class="l">최고가(원)</div></div></div>
            <?php if($refPrice): ?>
                <?php ($diff = $refPrice - $stats['min']); ?>
                <div class="adm-stat"><div>
                    <div class="v" style="color:<?php echo e($diff>0 ? '#e0322d' : '#16a34a'); ?>"><?php echo e($diff>0 ? '+' : ''); ?><?php echo e(number_format($diff)); ?></div>
                    <div class="l">메디셀−최저가 차이</div>
                </div></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <table class="atable">
            <thead><tr><th style="width:50px">순위</th><th>판매자(스토어)</th><th>상품명</th><th style="width:120px;text-align:right">판매가</th><th style="width:90px">배송</th><th style="width:110px">평점/리뷰</th><th style="width:90px">메디셀比</th><th style="width:60px"></th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><b><?php echo e($i+1); ?></b></td>
                    <td><b><?php echo e($r['seller']); ?></b></td>
                    <td><?php echo e($r['title']); ?></td>
                    <td style="text-align:right"><b><?php echo e(number_format($r['price'])); ?></b>원</td>
                    <td><?php if($r['rocket']): ?><span class="pill pill-b">로켓</span><?php else: ?><?php echo e($r['delivery']); ?><?php endif; ?></td>
                    <td>★ <?php echo e($r['rating']); ?> <span class="muted">(<?php echo e(number_format($r['review'])); ?>)</span></td>
                    <td>
                        <?php if($refPrice): ?>
                            <?php ($d = $r['price'] - $refPrice); ?>
                            <span style="color:<?php echo e($d<0 ? '#e0322d' : ($d>0 ? '#16a34a' : '#6b7794')); ?>;font-weight:700"><?php echo e($d>0?'+':''); ?><?php echo e(number_format($d)); ?></span>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td><a href="<?php echo e($r['url']); ?>" target="_blank" class="abtn abtn-ghost abtn-sm">쿠팡</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" style="text-align:center;color:#97a0b8;padding:40px">검색결과가 없습니다.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if($refPrice && $stats): ?>
            <div style="padding:14px 20px;border-top:1px solid var(--a-line);font-size:13.5px;color:#6b7794">
                <?php if($refPrice <= $stats['min']): ?>
                    ✅ 메디셀 판매가가 <b style="color:#16a34a">최저가 수준</b>입니다.
                <?php elseif($refPrice <= $stats['avg']): ?>
                    메디셀 판매가가 평균 이하입니다. 최저가(<?php echo e(number_format($stats['min'])); ?>원)보다 <?php echo e(number_format($refPrice-$stats['min'])); ?>원 높습니다.
                <?php else: ?>
                    ⚠️ 메디셀 판매가가 <b style="color:#e0322d">평균보다 높습니다</b>. 가격 조정 검토가 필요할 수 있습니다.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\medisell\resources\views/admin/coupang/index.blade.php ENDPATH**/ ?>