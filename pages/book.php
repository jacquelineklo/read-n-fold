<?php
include("includes/init.php");

$book_id = (int)trim($_GET['id']);
$url = "/book?" . http_build_query(array('id' => $book_id));
$delete_url = "/";

$style_feedback_class = 'hidden';
$summary_feedback_class = 'hidden';
$highlights_feedback_class = 'hidden';
$recommend_feedback_class = 'hidden';
$tags_feedback_class = 'hidden';

$get_tags = exec_sql_query($db, "SELECT * FROM tags")->fetchAll();
$tags = array();
for ($x = 0; $x < count($get_tags); $x = $x + 1) {
    $tags[$x] = $get_tags[$x]['tag_name'];
}

//get original tags from this book
$get_this_tags = exec_sql_query($db, "SELECT * FROM tags INNER JOIN book_tags ON tags.id = book_tags.tag_id WHERE book_tags.book_id = $book_id")->fetchAll();
$this_tags = array();
for ($x = 0; $x < count($get_this_tags); $x = $x + 1) {
    $this_tags[$x] = $get_this_tags[$x]['tag_name'];
}

$this_tags_id = array();
for ($x = 0; $x < count($get_this_tags); $x = $x + 1) {
    $this_tags_id[$x] = $get_this_tags[$x]['id'];
}

$edit_mode = False;
$edit_authorization = False;

if (isset($_GET['edit'])) {
    $edit_mode = True;
    $book_id = (int)trim($_GET['edit']);
}

if ($book_id) {
    $records = exec_sql_query(
        $db,
        "SELECT * FROM books WHERE id = :id;",
        array(':id' => $book_id)
    )->fetchAll();
    if (count($records) > 0) {
        $book = $records[0];
    } else {
        $book = NULL;
    }
}

$sticky_style = $book['reading_style'];
$sticky_summary = $book['summary'];
$sticky_highlights = $book['highlights'];
$sticky_recommend = $book['recommend'];

if ($book) {

    if ($current_user['id'] == $book['reviewer_id']) {
        $edit_authorization = True;
    } else {
        $edit_authorization = False;
    }
    $form_valid = False;

    if ($edit_authorization && isset($_POST['submit'])) {
        $summary = trim($_POST['summary']); // untrusted
        $recommend = trim($_POST['recommend']); // untrusted
        $highlights = trim($_POST['highlights']); // untrusted
        $reading_style = trim($_POST['reading-style']); // untrusted
        $form_valid = True;

        // summary is required
        if (empty($summary)) {
            $form_valid = False;
            $summary_feedback_class = '';
        }

        // book highlights
        if (empty($highlights)) {
            $form_valid = False;
            $highlights_feedback_class = '';
        }

        // reading style required
        if (empty($reading_style)) {
            $form_valid = False;
            $style_feedback_class = '';
        }

        // recommendation is required
        if (empty($recommend)) {
            $form_valid = False;
            $recommend_feedback_class = '';
        }

        // at least two tags are required
        $new_tags = array();
        $count = 0;
        foreach ($tags as $tag) {
            if (!empty(trim($_POST[$tag]))) {
                $new_tags[] = $tag;
                $count = $count + 1;
            }
        }
        if ($count < 2) {
            $form_valid = False;
            $tags_feedback_class = '';
        }

        if ($form_valid) {
            exec_sql_query(
                $db,
                "UPDATE books SET highlights = :highlights, summary = :summary, reading_style = :reading_style, recommend = :recommend WHERE (id = $book_id);",
                array(

                    'highlights' => $highlights,
                    'summary' => $summary,
                    'reading_style' => $reading_style,
                    'recommend' => $recommend
                )
            );

            //delete original books tags from db
            exec_sql_query($db, "DELETE FROM book_tags WHERE book_id= $book_id");


            foreach ($new_tags as $a_tag) {
                foreach ($get_tags as $tag) {
                    if ($a_tag == $tag['tag_name']) {
                        $this_tag_ids[] = $tag['id'];
                    }
                }
            }

            //add new tags to the database
            $this_book_id = $book_id;
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

            // get updated document
            $records = exec_sql_query(
                $db,
                "SELECT * FROM books WHERE id = :id;",
                array(':id' => $book_id)
            )->fetchAll();
            $book = $records[0];

            $edit_mode = false;
        } else {
            foreach ($new_tags as $tag) {
                $sticky_tags[ucfirst($tag)] = 'checked';
            }

            $sticky_style = $reading_style;
            $sticky_summary = $summary;
            $sticky_highlights = $highlights;
            $sticky_recommend = $recommend;
        }
    }

    $title = htmlspecialchars($book['title']);
    $author = htmlspecialchars($book['author']);
    $final_summary = htmlspecialchars($book['summary']);
    $final_highlights = htmlspecialchars($book['highlights']);
    $final_reading_style = htmlspecialchars($book['reading_style']);
    $final_recommend = htmlspecialchars($book['recommend']);

    $url = "/book?" . http_build_query(array('id' => $book['id']));
    $edit_url = "/book?" . http_build_query(array('edit' => $book['id']));

    $get_this_tags = exec_sql_query($db, "SELECT * FROM tags INNER JOIN book_tags ON tags.id = book_tags.tag_id WHERE book_tags.book_id = $book_id")->fetchAll();
    $this_tags = array();
    for ($x = 0; $x < count($get_this_tags); $x = $x + 1) {
        $this_tags[$x] = $get_this_tags[$x]['tag_name'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title> <?php echo $title; ?> </title>

    <link rel="stylesheet" type="text/css" href="/public/styles/styles.css" media="all" />
</head>

<body>
    <?php include("includes/header.php"); ?>


    <?php if ($edit_authorization && isset($_POST['delete'])) {
        exec_sql_query($db, "DELETE FROM book_tags WHERE book_id= $book_id");
        exec_sql_query($db, "DELETE FROM books WHERE id= $book_id");
    ?>
        <h2 class="delete-review"> Your review has successfully been deleted. </h2>
    <?php } else { ?>

        <main class="book">
            <section>
                <div class="book-author">
                    <div class="review-top">
                        <h2 class="title-head"><?php echo $title; ?></h2>
                        <h3> <?php echo $author; ?></h3>
                        <?php
                        $get_reviewer = exec_sql_query($db, "SELECT reviewers.name FROM reviewers INNER JOIN books ON reviewers.id = books.reviewer_id WHERE books.id = $book_id")->fetchAll();
                        $reviewer_name = $get_reviewer[0]['name'];
                        ?>
                        <h5>Reviewed by: <?php echo $reviewer_name ?> </h5>
                    </div>

                    <div>
                        <?php if ($book) { ?>
                            <img src="/public/uploads/books/<?php echo $book['id'] . '.' . $book['file_ext']; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" />
                    </div>
                </div>


                <?php if ($edit_authorization) { ?>
                    <?php if ($edit_mode) { ?>

                        <div class="edit-mode">
                            <form class="delete" method="post" novalidate>
                                <button type="submit" name="delete">Delete Entry</button>
                            </form>

                            <form class="edit" method="post" novalidate>


                                <div class="form-flex">
                                    <div class="form-flex2">
                                        <p class="feedback <?php echo $tags_feedback_class; ?>"> Please select at least two tags.</p>
                                        <p> Tags: </p>
                                        <?php
                                        if (isset($_POST['submit'])) {
                                            foreach ($tags as $tag) {

                                                if (!empty(trim($_POST[$tag]))) {
                                                    $new_tags[] = $tag;
                                                    $count = $count + 1;
                                                }
                                            }
                                            foreach ($new_tags as $tag) {
                                                $sticky_tags[ucfirst($tag)] = 'checked';
                                            }
                                        } else {
                                            foreach ($tags as $tag) {
                                                $sticky_tags[ucfirst($tag)] = '';
                                            }

                                            foreach ($this_tags as $tag) {
                                                $sticky_tags[ucfirst($tag)] = 'checked';
                                            }
                                        }
                                        foreach ($tags as $tag) {
                                        ?>
                                            <div>
                                                <label class="tag-label">
                                                    <input type="checkbox" name="<?php echo htmlspecialchars($tag); ?>" value="<?php echo htmlspecialchars($tag); ?>" <?php echo $sticky_tags[ucfirst($tag)]; ?> />
                                                    <?php echo htmlspecialchars(ucfirst($tag)); ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="form-flex2">

                                        <div class="text-area-container">
                                            <div>
                                                <div>
                                                    <label> Summary: </label>
                                                    <textarea id="summary" name="summary" required cols="40" rows="9"> <?php echo htmlspecialchars($sticky_summary); ?> </textarea>
                                                </div>
                                                <p class="feedback <?php echo $summary_feedback_class; ?>">Please provide a summary.</p>
                                            </div>

                                            <div>
                                                <div>
                                                    <label> Reading Style: </label>
                                                    <textarea id="reading-style" name="reading-style" required cols="40" rows="9"><?php echo htmlspecialchars($sticky_style); ?></textarea>
                                                </div>
                                                <p class="feedback <?php echo $style_feedback_class; ?>">Please describe how you read the book.</p>
                                            </div>
                                        </div>


                                        <div class="text-area-container">
                                            <div>
                                                <div>
                                                    <label> Recommend: </label>
                                                    <textarea id="recommend" name="recommend" required cols="40" rows="9"><?php echo htmlspecialchars($sticky_recommend); ?> </textarea>
                                                </div>
                                                <p class="feedback <?php echo $recommend_feedback_class; ?>">Please provide a recommendation.</p>
                                            </div>
                                            <div>
                                                <div>
                                                    <label> Highlights: </label>
                                                    <textarea id="highlights" name="highlights" required cols="40" rows="9"><?php echo htmlspecialchars($sticky_highlights); ?></textarea>
                                                </div>
                                                <p class="feedback <?php echo $highlights_feedback_class; ?>">Please describe some of the highlights.</p>
                                            </div>
                                        </div>

                                        <button type="submit" name="submit">Save</button>

                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php
                                } else { ?>

                        <div class="review-content">
                            <h2 class="review-header"> The Review

                            </h2>

                            <p> Tags:
                                <?php
                                    $phrase = '';
                                    $count = 0;

                                    for ($x = 0; $x < count($this_tags); $x = $x + 1) {
                                        if ($count + 1 == count($this_tags)) {
                                            $phrase = $phrase . ucfirst($this_tags[count($this_tags) - 1]);
                                        } else {
                                            $phrase = $phrase . ucfirst($this_tags[$x]) . ', ';
                                        }
                                        $count = $count + 1;
                                    }
                                    echo $phrase;
                                ?>
                                (<a href="<?php echo $edit_url; ?>">Edit</a>)
                            </p>

                            <p> Summary:
                                <?php echo htmlspecialchars($final_summary); ?>
                                (<a href="<?php echo $edit_url; ?>">Edit</a>)
                            </p>

                            <p> How it was read:
                            <?php echo htmlspecialchars($final_reading_style); ?>
                            (<a href="<?php echo $edit_url; ?>">Edit</a>)
                            </p>

                            <p> Recommend?
                                <?php echo htmlspecialchars($final_recommend); ?>
                                (<a href="<?php echo $edit_url; ?>">Edit</a>)
                            </p>

                            <p> Highlights:
                                <?php echo htmlspecialchars($final_highlights); ?>
                                (<a href="<?php echo $edit_url; ?>">Edit</a>)
                            </p>

                            <p> <cite> Source:
                                    <?php echo htmlspecialchars($book['source']); ?> </cite>
                            </p>
                        <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="review-content">
                            <h3> The Review </h3>

                            <p> Genres:
                                <?php
                                $phrase = '';
                                $count = 0;

                                for ($x = 0; $x < count($this_tags); $x = $x + 1) {

                                    if ($count + 1 == count($this_tags)) {
                                        $phrase = $phrase . ucfirst($this_tags[count($this_tags) - 1]);
                                    } else {
                                        $phrase = $phrase . ucfirst($this_tags[$x]) . ', ';
                                    }
                                    $count = $count + 1;
                                }
                                echo $phrase;
                                ?>
                            </p>

                            <p> Summary:
                                <?php echo htmlspecialchars($final_summary); ?>
                            </p>

                            <p> How it was read:
                                <?php echo htmlspecialchars($final_reading_style); ?>
                            </p>

                            <p> Recommend?
                                <?php echo htmlspecialchars($final_recommend); ?>
                            </p>

                            <p> Highlights:
                                <?php echo htmlspecialchars($final_highlights);?>
                            </p>

                            <p> <cite>
                                    Source: <a href="<?php echo htmlspecialchars($book['source']); ?>"><?php echo htmlspecialchars($book['source']); ?></a>
                                </cite> </p>
                        <?php  }  ?>

                        </div>
                    <?php } else { ?>
                        <p><strong>The document you were looking for does not exist.</strong> Try locating the book from the <a href="/book">book title</a>.</strong></p>
                    <?php } ?>
            </section>
        </main>
    <?php } ?>

    <?php include("includes/footer.php"); ?>
</body>

</html>
