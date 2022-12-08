<?php
include("includes/init.php");

$title = "Submission";
$nav_submission_class = "current_page";
$border = "active";

define("MAX_FILE_SIZE", 1000000);

$title_feedback_class = 'hidden';
$author_feedback_class = 'hidden';
$tags_feedback_class = 'hidden';
$summary_feedback_class = 'hidden';
$recommend_feedback_class = 'hidden';
$highlights_feedback_class = 'hidden';
$reading_style_feedback_class = 'hidden';
$file_feedback_class = 'hidden';
$desc_feedback_class = 'hidden';
$source_feedback_class = 'hidden';

$review_inserted = False;
$review_insert_failed = False;

$user_title = NULL;
$user_author = NULL;
$user_tags = NULL;
$user_reviewer = NULL;
$user_summary = NULL;
$user_recommend = NULL;
$user_highlights = NULL;
$user_style = NULL;

$upload_desc = NULL;
$upload_source = NULL;
$upload_filename = NULL;
$upload_ext = NULL;

$get_tags = exec_sql_query($db, "SELECT * FROM tags")->fetchAll();
$tags = array();
for ($x = 0; $x < count($get_tags); $x = $x + 1) {
  $tags[$x] = $get_tags[$x]['tag_name'];
}

$sticky_tags = array();

$sticky_title = '';
$sticky_author = '';
$sticky_summary = '';
$sticky_recommend = '';
$sticky_highlights = '';
$sticky_style = '';
$sticky_desc = '';
$sticky_source = '';

$get_books = exec_sql_query($db, "SELECT * FROM books")->fetchAll();
$books = array();
for ($x = 0; $x < count($get_books); $x = $x + 1) {
  $books[$x] = strtolower($get_books[$x]['title']);
}

if (is_user_logged_in()) {

  if (isset($_POST['submit'])) {
    $upload_source = trim($_POST['upload-source']); // untrusted
    $user_title = trim($_POST['user_title']); // untrusted
    $user_author = trim($_POST['user_author']); // untrusted
    $user_summary = trim($_POST['user_summary']); // untrusted
    $user_recommend = trim($_POST['user_recommend']); // untrusted
    $user_highlights = trim($_POST['user_highlights']); // untrusted
    $user_style = trim($_POST['user_style']); // untrusted

    $form_valid = True;
    $upload = $_FILES['jpeg-file'];

    if ($upload['error'] == UPLOAD_ERR_OK) {
      $upload_filename = basename($upload['name']);
      $upload_ext = strtolower(pathinfo($upload_filename, PATHINFO_EXTENSION));

      if (!in_array($upload_ext, array('jpeg'))) {
        $form_valid = false;
      }
    } else {
      $form_valid = false;
    }

    if (empty($upload_source)) {
      $form_valid = False;
      $source_feedback_class = '';
    }

    // book title is required and must be UNIQUE
    if (empty($user_title) || in_array(strtolower($user_title), $books)) {
      $form_valid = False;
      $title_feedback_class = '';
    }

    // book author must be valid
    if (empty($user_author)) {
      $form_valid = False;
      $author_feedback_class = '';
    }

    // at least two tags are required
    $count = 0;
    foreach ($tags as $tag) {

      if (!empty(trim($_POST[$tag]))) {
        $count = $count + 1;
      }
    }
    if ($count < 2) {
      $form_valid = False;
      $tags_feedback_class = '';
    }

    // summary is required
    if (empty($user_summary)) {
      $form_valid = False;
      $summary_feedback_class = '';
    }

    // book highlights
    if (empty($user_highlights)) {
      $form_valid = False;
      $highlights_feedback_class = '';
    }

    // reading style required
    if (empty($user_style)) {
      $form_valid = False;
      $reading_style_feedback_class = '';
    }

    // recommendation is required
    if (empty($user_recommend)) {
      $form_valid = False;
      $recommend_feedback_class = '';
    }

    if ($form_valid) {
      $user_reviewer = $current_user['id'];
      $db->beginTransaction();

      $result = exec_sql_query(
        $db,
        "INSERT INTO books(title, author, reviewer_id, summary, reading_style, recommend, highlights, file_ext, source) VALUES (:title, :author, :reviewer_id, :summary, :reading_style, :recommend, :highlights, :file_ext, :source);",
        array(
          'title' => $user_title,
          'author' => $user_author,
          'reviewer_id' => $user_reviewer,
          'summary' => $user_summary,
          'reading_style' => $user_style,
          'recommend' => $user_recommend,
          'highlights' => $user_highlights,
          ':file_ext' => $upload_ext,
          ':source' => $upload_source
        )
      );

      $db->commit();

      if ($result) {

        $record_id = $db->lastInsertId('id');

        $id_filename = 'public/uploads/books/' . $record_id . '.' . $upload_ext;
        move_uploaded_file($upload["tmp_name"], $id_filename);
      }

      $this_tag_ids = array();
      $this_tags = array();
      foreach ($tags as $tag) {
        if (!empty(trim($_POST[$tag]))) {
          $this_tags[] = $tag;
        }
      }

      foreach ($this_tags as $a_tag) {
        foreach ($get_tags as $tag) {
          if ($a_tag == $tag['tag_name']) {
            $this_tag_ids[] = $tag['id'];
          }
        }
      }

      $this_book_id = $db->lastInsertId('id');
      foreach ($this_tag_ids as $id) {
        exec_sql_query(
          $db,
          "INSERT INTO book_tags(book_id, tag_id) VALUES (:book_id, :tag_id)",
          array(
            'book_id' => $this_book_id,
            'tag_id' => $id
          )
        );
      }

      if ($result) {
        $review_inserted = True;
      } else {
        $review_insert_failed = True;
      }
    } else {

      $sticky_title = $user_title;
      $sticky_author = $user_author;

      foreach ($tags as $tag) {

        if (!empty(trim($_POST[$tag]))) {

          $sticky_tags[ucfirst($tag)] = 'checked';
        }
      }

      $sticky_reviewer = $user_reviewer;
      $sticky_summary =  $user_summary;
      $sticky_style = $user_style;
      $sticky_recommend = $user_recommend;
      $sticky_highlights = $user_highlights;

      $file_feedback_class = '';

      $sticky_source = $upload_source;
    }
  }
}
?>













<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title> <?php echo $title ?> </title>

  <link rel="stylesheet" type="text/css" href="/public/styles/styles.css" media="all" />
</head>

<body>

  <?php include("includes/header.php"); ?>

  <?php if ($review_inserted) { ?>
    <p class="confirmation"><strong>Thank you for submitting a book review.</strong></p>
  <?php } ?>

  <?php if ($review_insert_failed) { ?>
    <p class="feedback"><strong>Something went wrong submitting your book review. Please try again.</strong></p>
  <?php } ?>

  <?php if (!is_user_logged_in()) {
  ?>
    <div class="sign-in">
      <p> You must sign in to submit a book review. </p>

    <?php
    echo_login_form($url, $session_messages);
  } ?>
    </div>

    <?php if (is_user_logged_in()) { ?>

      <p class="center"> Upload a book cover: </p>
      <section>
        <div class="form-align">
          <form method="post" enctype="multipart/form-data" novalidate>

            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>" />
            <input class="upload" type="file" name="jpeg-file" accept=".jpeg,image/jpeg+xml">

            <p class="feedback <?php echo $file_feedback_class; ?>">Please select a JPEG file.</p>

            <div class="group_label_input">
              <label for="upload-file">JPEG File:</label>
              <input id="upload-file" name="jpeg-file" required />
            </div>

            <p class="feedback <?php echo $source_feedback_class; ?>">Please provide an upload source.</p>
            <div class="group_label_input">
              <label for="upload-source">Source:</label>
              <input id="upload-source" type="text" name="upload-source" required value="<?php echo htmlspecialchars($sticky_source); ?>" />
            </div>

            <p id="title_feedback" class="feedback <?php echo $title_feedback_class; ?>">Please provide a unique book title.</p>
            <div class="group_label_input">
              <label for="user_title">Book Title:</label>
              <input id="user_title" type="text" name="user_title" value="<?php echo htmlspecialchars($sticky_title); ?>" required />
            </div>

            <p id="author_feedback" class="feedback <?php echo $author_feedback_class; ?>">Please provide the author's name.</p>
            <div class="group_label_input">
              <label for="user_author">Author:</label>
              <input id="user_author" type="text" name="user_author" value="<?php echo htmlspecialchars($sticky_author); ?>" required />
            </div>

            <p id="tags_feedback" class="feedback <?php echo $tags_feedback_class; ?>">Please pick at least two book tags.</p>
            <div class="tags-flex">

              <div>
                <p class="label_align">Tags:</p>
              </div>


              <div class="reasons-flex">
                <?php
                foreach ($tags as $tag) {
                ?>
                  <div>
                    <label>
                      <input type="checkbox" name="<?php echo htmlspecialchars($tag); ?>" value="<?php echo htmlspecialchars(ucfirst($tag)); ?>" <?php echo $sticky_tags[ucfirst($tag)]; ?> />
                      <?php echo htmlspecialchars(ucfirst($tag)); ?>
                    </label>
                  </div>
                <?php } ?>
              </div>
            </div>

            <p id="summary_feedback" class="feedback <?php echo $summary_feedback_class; ?>">Please provide a one sentence summary.</p>
            <div class="group_label_input">
              <label for="user_summary">One sentence summary:</label>
              <textarea id="user_summary" name="user_summary" cols="40" rows="3"><?php echo htmlspecialchars($sticky_summary); ?></textarea>
            </div>

            <p id="reading_style_feedback" class="feedback <?php echo $reading_style_feedback_class; ?>">Please describe how you read the book.</p>
            <div class="group_label_input">
              <label for="user_style">How it was read:</label>
              <textarea id="user_style" name="user_style" cols="40" rows="3"><?php echo htmlspecialchars($sticky_style); ?></textarea>
            </div>

            <p id="recommend_feedback" class="feedback <?php echo $recommend_feedback_class; ?>">Please explain whether you do or do not recommend this book.</p>
            <div class="group_label_input">
              <label for="user_recommend">Recommend?(yes/no and why!):</label>
              <textarea id="user_recommend" name="user_recommend" cols="40" rows="6"><?php echo htmlspecialchars($sticky_recommend); ?></textarea>
            </div>

            <p id="highlights_feedback" class="feedback <?php echo $highlights_feedback_class; ?>">Please describe the highlights of this book.</p>
            <div class="group_label_input">
              <label for="user_highlights">Highlights (writing style, plot, historical content, etc.):</label>
              <textarea id="user_highlights" name="user_highlights" cols="40" rows="6"><?php echo htmlspecialchars($sticky_highlights); ?></textarea>
            </div>

            <div class="align-right">
              <button type="submit" name="submit">Submit Review</button>
            </div>
          </form>
        </div>
      </section>
    <?php } ?>

    <?php include("includes/footer.php"); ?>

</body>

</html>
