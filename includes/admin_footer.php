        <?php if(isset($listPager)): uiPagination($listPager['page'],$listPager['pages'],$listPager['total'],'data'); endif; ?>
        </main>
    </div>

    <!-- Footer -->
    <?php require __DIR__ . '/portal_footer.php'; ?>

</body>
</html>
