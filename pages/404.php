<?php
include("includes/init.php");
$title = "Not Found";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title><?php echo $title; ?> Book Reviews</title>

  <link rel="stylesheet" type="text/css" href="/public/styles/styles.css" media="all" />
</head>

<body>
  <?php include("includes/header.php"); ?>

  <main>
    <h2 class = "confirmation"><?php echo $title; ?></h2>
    <p class = "center">I'm sorry. The page you were looking for, <em>&quot;<?php echo htmlspecialchars($request_uri); ?>&quot;</em>, does not exist.</p>
  </main>

  <?php include("includes/footer.php"); ?>
</body>

</html>
