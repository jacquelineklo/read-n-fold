<?php
include("includes/init.php");

$title = "Home";
$nav_home_class = "current_page";

$sticky_tags = array();
$space = ' ';

$sql_filter_exprs = '';
$has_filtering = False;

$sql_select_query = 'SELECT * FROM books ORDER BY title ASC;';


$get_tags = exec_sql_query($db, "SELECT * FROM tags")->fetchAll();
$tags = array();
for ($x = 0; $x < count($get_tags); $x = $x + 1) {
  $tags[$x] = $get_tags[$x]['tag_name'];
}

//ADDING A NEW TAG
$form_valid = False;
$tag_feedback_class = 'hidden';
$new_tag = NULL;

if (isset($_POST['submit'])) {
  $new_tag = trim($_POST['tag']);
  $form_valid = True;

  $space_bool = false;
  if (strpos($new_tag, $space) != false) {
    $space_bool = true;
  }

  if (empty($new_tag) || in_array(strtolower($new_tag), $tags) || $space_bool) {
    $form_valid = False;
    $tag_feedback_class = '';
  }
}

if ($form_valid) {
  $result = exec_sql_query(
    $db,
    "INSERT INTO tags(tag_name) VALUES (:tag_name);",
    array(
      'tag_name' => strtolower($new_tag)
    )
  );
} else {
  $sticky_tag = $new_tag;
}

$get_tags = exec_sql_query($db, "SELECT * FROM tags")->fetchAll();
$tags = array();
for ($x = 0; $x < count($get_tags); $x = $x + 1) {
  $tags[$x] = $get_tags[$x]['tag_name'];
}

foreach ($tags as $tag) {

  if ($_GET[$tag]) {
    $has_filtering = True;
    $sticky_tags[ucfirst($tag)] = 'checked';

    if (empty($sql_filter_exprs)) {
      $sql_filter_exprs = "SELECT DISTINCT books.id, books.title, books.author, books.reviewer_id, books.summary, books.reading_style, books.recommend, books.highlights, books.file_ext, books.source FROM books INNER JOIN book_tags ON books.id = book_tags.book_id INNER JOIN tags ON book_tags.tag_id = tags.id WHERE tags.tag_name = " . "'" . $tag . "'";
    } else {
      $sql_filter_exprs = $sql_filter_exprs . " OR tags.tag_name = " . "'" . $tag . "'";
    }
  }
}

if ($has_filtering) {
  $sql_select_query = $sql_filter_exprs;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title> Book Reviews Home </title>

  <link rel="stylesheet" type="text/css" href="/public/styles/styles.css" media="all" />
</head>

<body>

  <?php include("includes/header.php"); ?>

  <div class="flex">
    <div class="flex-box-content1">

      <h3 class="class-search">Genres </h3>

      <form class="filter" method="get" novalidate>

        <?php
        foreach ($tags as $tag) { ?>
          <div>
            <label>
              <input type="checkbox" name="<?php echo htmlspecialchars($tag); ?>" value="1" <?php echo $sticky_tags[ucfirst($tag)]; ?> />
              <?php echo htmlspecialchars(ucfirst($tag)); ?>
            </label>
          </div>
        <?php } ?>
        <button type="submit">Filter</button>
      </form>


      <?php if (is_user_logged_in()) { ?>

        <h3 class="class-search">Add a Genre </h3>

        <form class="tag-align" method="post" novalidate>

          <p id="tag_feedback" class="tag-feedback <?php echo $tag_feedback_class; ?>">Please enter a unique, one word genre.</p>

          <label for="tag"> Name: </label>
          <div>
            <input id="tag" type="text" name="tag" value="<?php echo htmlspecialchars($sticky_tag); ?>" required />
          </div>
          <div>
            <button type="submit" name="submit">Submit</button>
          </div>

        </form>

      <?php } ?>

    </div>

    <div class="flex-box-content2">

      <?php if ($has_filtering) { ?>
        <h2 class="filter-results"> Filter Results</h2>
      <?php } else { ?>
        <h2 class="filter-results">All Books</h2> <?php } ?>

      <?php
      $records = exec_sql_query($db, $sql_select_query)->fetchAll();
      ?>

      <?php
      if (count($records) > 0) {
        for ($x = 0; $x < count($records); $x = $x + 2) {
      ?>
          <div class="row">
            <div class="row-test">

              <a class="book-title" href="/book?<?php echo http_build_query(array('id' => $records[$x]['id'])); ?>">
                <img class="book-pics" src="/public/uploads/books/<?php echo $records[$x]['id'] . '.' . $records[$x]['file_ext'] ?>" alt="<?php echo htmlspecialchars($record[$x]['title']); ?>" />
                <p class="book-title"><?php echo ucfirst($records[$x]['title']); ?></p>
              </a>

            </div>

            <div>

              <a class="book-title" href="/book?<?php echo http_build_query(array('id' => $records[$x + 1]['id'])); ?>">
                <img class="book-pics" src="/public/uploads/books/<?php echo $records[$x + 1]['id'] . '.' . $records[$x + 1]['file_ext']; ?>" alt="<?php echo htmlspecialchars($records[$x + 1]['title']); ?>" />
                <p class="book-title"><?php echo ucfirst($records[$x + 1]['title']); ?></p>
              </a>

            </div>

          </div>

        <?php } ?>

      <?php } else { ?>
        <p> No books found. </p>
      <?php } ?>

    </div>

  </div>

  <?php include("includes/footer.php"); ?>

</body>

</html>
