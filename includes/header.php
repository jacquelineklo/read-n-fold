<header>
    <div class="container">
        <div class="centered">
            <h1 class="title">Book Reviews</h1>
        </div>
        <nav>
            <ul>
                <li class="<?php echo $nav_home_class; ?>"><a class="nav-stuff" href="/">HOME</a></li>

                <?php if (!is_user_logged_in()) { ?>
                    <li class="<?php echo $nav_submission_class; ?>"><a class="nav-stuff" href="/submission">SIGN IN</a></li>
                <?php } else { ?>
                    <li class="<?php echo $nav_submission_class; ?>"><a class="nav-stuff" href="/submission">SUBMIT A REVIEW</a></li>
                <?php } ?>

                <?php if (is_user_logged_in()) { ?>
                    <li class="bottom-right"><a class="nav-stuff" href="<?php echo logout_url(); ?>">SIGN OUT</a></li>
                <?php } ?>

            </ul>
        </nav>
    </div>
</header>
