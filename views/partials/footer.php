<?php
/**
 * Footer (Atelier Step 6). Front: light footer with a hairline, restyled from the eLEARNING footer. Back office: TailAdmin-style footer.
 * Variable: $office (front|owner|admin).
 * The "Designed By HTML Codex / Distributed By ThemeWagon" credit must stay: it is the template's licence condition.
 */
$office = $office ?? 'front';
?>
<?php if ($office === 'front') : ?>
    <!-- Footer Start -->
    <footer class="ds-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h2>SKYDROP</h2>
                    <p class="mb-2">Find a product in stock at a shop near you, then pick it up or have it delivered.</p>
                    <p class="mb-0"><span class="simulation-badge">Simulation</span> Drone and electric-car delivery, map positions and payment are simulated.</p>
                </div>
                <div class="col-6 col-md-3">
                    <h2>Shop</h2>
                    <ul>
                        <?php foreach (Registry::menu('front') as [$key, $label]) : ?>
                            <li><a href="<?= e(route($key)) ?>"><?= e($label) ?></a></li>
                        <?php endforeach; ?>
                        <?php if (!Auth::check()) : ?>
                            <li><a href="<?= e(route('register')) ?>">Create an account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-6 col-md-4">
                    <h2>Project</h2>
                    <ul>
                        <li>Developed at Esprit School of Engineering &ndash; Tunisia</li>
                        <li>PIDEV, class 2A8, 2026&ndash;2027</li>
                    </ul>
                </div>
            </div>
            <p class="ds-credit mb-0">
                &copy; <?= date('Y') ?> SKYDROP. Academic project, no real transactions.
                <!--/*** This template is free as long as you keep the footer author's credit link/attribution link/backlink. ***/-->
                Designed By <a href="https://htmlcodex.com">HTML Codex</a>, Distributed By <a href="https://themewagon.com">ThemeWagon</a>.
            </p>
        </div>
    </footer>
    <!-- Footer End -->
<?php else : ?>
    <!-- ===== Footer Start ===== -->
    <footer class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; <?= date('Y') ?> SKYDROP &middot; Developed at Esprit School of Engineering &ndash; Tunisia &middot;
            Delivery, positions and payments are simulated &middot; Template: TailAdmin
        </p>
    </footer>
    <!-- ===== Footer End ===== -->
<?php endif; ?>
