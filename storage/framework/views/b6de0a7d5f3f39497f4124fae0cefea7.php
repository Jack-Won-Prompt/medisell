<?php if($paginator->hasPages()): ?>
    <?php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 2;                     // 현재 페이지 양옆 표시 개수
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);
    ?>
    <nav class="pagination" role="navigation" aria-label="페이지 네비게이션">
        
        <?php if($paginator->onFirstPage()): ?>
            <span aria-disabled="true">&lsaquo;</span>
        <?php else: ?>
            <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev">&lsaquo;</a>
        <?php endif; ?>

        
        <?php if($start > 1): ?>
            <a href="<?php echo e($paginator->url(1)); ?>">1</a>
            <?php if($start > 2): ?><span class="dots" aria-disabled="true">&hellip;</span><?php endif; ?>
        <?php endif; ?>

        
        <?php for($page = $start; $page <= $end; $page++): ?>
            <?php if($page == $current): ?>
                <span class="active"><span><?php echo e($page); ?></span></span>
            <?php else: ?>
                <a href="<?php echo e($paginator->url($page)); ?>"><?php echo e($page); ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        
        <?php if($end < $last): ?>
            <?php if($end < $last - 1): ?><span class="dots" aria-disabled="true">&hellip;</span><?php endif; ?>
            <a href="<?php echo e($paginator->url($last)); ?>"><?php echo e($last); ?></a>
        <?php endif; ?>

        
        <?php if($paginator->hasMorePages()): ?>
            <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next">&rsaquo;</a>
        <?php else: ?>
            <span aria-disabled="true">&rsaquo;</span>
        <?php endif; ?>
    </nav>
<?php endif; ?>
<?php /**PATH E:\xampp\htdocs\medisell\resources\views/pagination/simple.blade.php ENDPATH**/ ?>