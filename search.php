<?php
/**
 * search.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;

/**
 * The search page
 * Here be dragons!
 *
 */
require_once 'app/init.inc.php';
$page_title = _('Search');
$selected_menu = 'Search';
require_once 'app/head.inc.php';

$ItemsTypes = new ItemsTypes($_SESSION['team_id']);
$Status = new Status($_SESSION['team_id']);

// make array of results id
$results_arr = array();
$search_type = '';

// TYPE
if (isset($_GET['type']) && $_GET['type'] === 'database') {
    $seldb = " selected='selected'";
} else {
    $seldb = "";
}

// FROM
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from = Tools::kdate($_GET['from']);
} else {
    $from = '';
}

// TO
if (isset($_GET['to']) && !empty($_GET['to'])) {
    $to = Tools::kdate($_GET['to']);
} else {
    $to = '';
}

$title = '';
$titleWithSpace = false;
// TITLE
if (isset($_GET['title']) && !empty($_GET['title'])) {
    // check if there is a space in the query
    if (strrpos(trim($_GET['title']), " ") !== false) {
        $title_arr = explode(' ', trim($_GET['title']));
        $titleWithSpace = true;
    }
    $title = filter_var(trim($_GET['title']), FILTER_SANITIZE_STRING);
}

// BODY
$body = '';
$bodyWithSpace = false;
if (isset($_GET['body']) && !empty($_GET['body'])) {
    if (strrpos(trim($_GET['body']), " ") !== false) {
        $body_arr = explode(' ', trim($_GET['body']));
        $bodyWithSpace = true;
    }
    $body = filter_var(Tools::checkBody(trim($_GET['body'])), FILTER_SANITIZE_STRING);
}

// ANDOR
$andor = ' AND ';
if (isset($_GET['andor']) && ($_GET['andor'] === 'and')) {
    $andSel = " selected='selected'";
} else {
    $andSel = '';
}
if (isset($_GET['andor']) && ($_GET['andor'] === 'or')) {
    $orSel = " selected='selected'";
    $andor = ' OR ';
} else {
    $orSel = '';
}
?>

<!-- Search page begin -->
<section class='searchform box'>
    <form name="search" method="get" action="search.php">
        <div class='row'>
            <!-- SEARCH IN-->
            <div class='col-md-3'>
                <label for='searchin'><?= _('Search in') ?></label>
                <select name='type' id='searchin'>
                    <option value='experiments'><?= ngettext('Experiment', 'Experiments', 2) ?></option>
                    <option disabled>----------------</option>
                    <option value='database'<?= $seldb ?>><?= _('Database') ?></option>
<?php // Database items types
$itemsTypesArr = $ItemsTypes->readAll();
foreach ($itemsTypesArr as $items_types) {
    echo "<option value='" . $items_types['id'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['type']) && $items_types['id'] == $_GET['type']) {
        echo " selected='selected'";
    }
    echo ">- " . $items_types['name'] . "</option>";
}
?>
                </select>
            </div>
            <!-- END SEARCH IN -->
            <!-- SEARCH WITH TAG -->
<?php
// get the list of tags to display

// we want the tags of everyone in the team if we search for the whole team's experiments
if (isset($_GET['owner']) && ($_GET['owner'] === '0')) {

    $sql = "SELECT tag, COUNT(*) as nbtag
        FROM experiments_tags
        INNER JOIN users ON (experiments_tags.userid = users.userid)
        WHERE users.team = :team
        GROUP BY tag ORDER BY tag ASC";
    $req = $pdo->prepare($sql);
    $req->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
    $req->execute();

} else {

    $sql = "SELECT tag, COUNT(*) as nbtag
        FROM experiments_tags
        WHERE userid = :userid
        GROUP BY tag ORDER BY tag ASC";
    $req = $pdo->prepare($sql);
    // we want to show the tags of the selected person in 'search in' dropdown
    // so if there is a owner parameter, use it to select tags
    if (isset($_GET['owner']) && Tools::checkId($_GET['owner'])) {
        $userid = $_GET['owner'];
    } else {
        $userid = $_SESSION['userid'];
    }
    $req->bindParam(':userid', $userid, PDO::PARAM_INT);
    $req->execute();
}
?>

            <div class='col-md-3' id='tag_exp'>
                <label for='tag_exp'><?php echo _('With the tag'); ?></label>
                <select name='tag_exp' style='max-width:80%'>
                    <option value=''><?php echo _('Select a Tag'); ?></option>

<?php
while ($exp_tags = $req->fetch()) {
    echo "<option value='" . $exp_tags['tag'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['tag_exp']) && ($exp_tags['tag'] == $_GET['tag_exp'])) {
        echo " selected='selected'";
    }
    echo ">" . $exp_tags['tag'] . " (" . $exp_tags['nbtag'] . ")</option>";
}
?>

                </select>
            </div>

            <div class='col-md-3' id='tag_db'>
                <label for='tag_db'><?php echo _('With the tag'); ?></label>
                <select name='tag_db'>
                    <option value=''><?php echo _('Select a tag'); ?></option>

<?php // Database items types
// TODO here we should show only the tags linked with the type of item selected in the 'searchin' select
$sql = "SELECT tag, COUNT(*) as nbtag FROM items_tags WHERE team_id = :team GROUP BY tag ORDER BY tag ASC";
$req = $pdo->prepare($sql);
$req->bindParam(':team', $_SESSION['team_id'], PDO::PARAM_INT);
$req->execute();

while ($items_types = $req->fetch()) {
    echo "<option value='" . $items_types['tag'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['tag_db']) && ($items_types['tag'] == $_GET['tag_db'])) {
        echo " selected='selected'";
    }
    echo ">" . $items_types['tag'] . " (" . $items_types['nbtag'] . ")</option>";
}
?>
                </select>
            </div>
            <!-- END SEARCH WITH TAG -->

            <!-- SEARCH ONLY -->
            <div class='col-md-6'>
                <label for'searchonly'><?php echo _('Search only in experiments owned by:'); ?> </label><br>
                <!-- when you change this select, you reload the page so the tag selector loads the correct tags -->
                <select id='searchonly' name='owner'>
                    <option value=''><?php echo _('Yourself'); ?></option>
                    <!-- add an option to search in the whole team (owner = 0) -->
                    <option value='0'
<?php
if (isset($_GET['owner']) && ($_GET['owner'] === '0')) {
    echo " selected='selected'";
}
echo ">" . _("All the team"); ?></option>
<option disabled>----------------</option>
<?php
    $users_sql = "SELECT userid, CONCAT (firstname, ' ', lastname) AS name
    FROM users WHERE team = :team ORDER BY firstname ASC";
$users_req = $pdo->prepare($users_sql);
$users_req->execute(array(
    'team' => $_SESSION['team_id']
));
while ($users = $users_req->fetch()) {
    echo "<option value='" . $users['userid'] . "'";
    // item get selected if it is in the search url
    if (isset($_GET['owner']) && ($users['userid'] == $_GET['owner'])) {
        echo " selected='selected'";
    }
    echo ">" . $users['name'] . "</option>";
}
?>
                </select><br>
            </div>
            <!-- END SEARCH ONLY -->
        </div>

        <!-- SEARCH DATE -->
        <div class='row'>
            <div class='col-md-8'>
                <label for='from'><?= _('Where date is between') ?></label>
                <input id='from' name='from' type='text' size='8' class='datepicker' value='<?= $from ?>'/>
                <label span style='margin:0 10px;' for='to'> <?php echo _('and'); ?> </label>
                <input id='to' name='to' type='text' size='8' class='datepicker' value='<?= $to ?>'/>
            </div>
        </div>
        <!-- END SEARCH DATE -->

        <!-- TITLE -->
        <div class='row'>
            <div class='col-md-6'>
            <label for='title'><?php echo _('And title contains'); ?></label>
            <input id='title' name='title' type='text' value='<?= $title ?>'/>
            </div>
            <!-- STATUS -->
            <div class='col-md-4'>
                <label for='status'><?= _('And status is') ?></label>
                <select id='status' name="status">
                    <option value=''><?= _('select status') ?></option>
<?php
$statusArr = $Status->readAll();
foreach ($statusArr as $status) {
    echo "<option ";
    if (isset($_GET['status']) && ($_GET['status'] == $status['id'])) {
        echo "selected ";
    }
    echo "value='" . $status['id'] . "'>" . $status['name'] . "</option>";
}
?>
                </select>
            </div>

        </div>
        <div class='row'>
            <div class='col-md-6'>
            <label for='body'><?= _('And body contains') ?></label>
            <input id='body' name='body' type='text' value='<?= $body ?>'/>
            <!-- AND / OR -->
                <select id='andor' name='andor'>
                <option value='' disabled selected><?= _('Space means') ?></option>
                <option value='and' <?= $andSel ?>><?= _('and') ?></option>

                <option value='or' <?= $orSel ?>><?= _('or') ?></option>
                </select>
            </div>
            <!-- END TITLE/BODY block -->

            <!-- RATING -->
            <div class='col-md-4'>
                <label for='rating'><?= _('And rating is') ?></label>
                <select id='rating' name='rating'>
                    <option value=''><?= _('select number of stars') ?></option>
                    <option value='no'><?= _('Unrated') ?></option>
<?php
for ($i = 1; $i <= 5; $i++) {
    echo "<option value='" . $i . "'";
    // item get selected if it is in the search url
    if (isset($_GET['rating']) && ($_GET['rating'] == $i)) {
        echo " selected='selected'";
    }
    echo ">" . $i . "</option>";
}?>
                </select>
            </div>
            <!-- END RATING -->
        </div>

        <div style='margin:30px;' class='center'>
            <button id='searchButton' class='button' value='Submit' type='submit'><?= _('Launch search') ?></button>
        </div>
    </form>
</section>


<?php
/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if (isset($_GET)) {
    // assign variables from get

    // TAGS
    if (isset($_GET['tag_exp']) && !empty($_GET['tag_exp']) && isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $tags = filter_var($_GET['tag_exp'], FILTER_SANITIZE_STRING);
    } elseif (isset($_GET['tag_db']) && !empty($_GET['tag_db']) && isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] !== 'experiments') {
        $tags = filter_var($_GET['tag_db'], FILTER_SANITIZE_STRING);
    } else {
        $tags = '';
    }

    // STATUS
    if (isset($_GET['status']) && !empty($_GET['status']) && Tools::checkId($_GET['status'])) {
        $status = $_GET['status'];
    } else {
        $status = '';
    }

    // RATING
    if (isset($_GET['rating']) && !empty($_GET['rating'])) {
        if ($_GET['rating'] === 'no') {
            $rating = '0';
        } else {
            $rating = intval($_GET['rating']);
        }
    } else {
        $rating = '';
    }

    // OWNER
    if (isset($_GET['owner']) && !empty($_GET['owner']) && Tools::checkId($_GET['owner'])) {
        $owner_search = true;
        $owner = $_GET['owner'];
    } else {
        $owner_search = false;
        $owner = '';
    }

    // PREPARE SQL query
    if (isset($_GET['type']) && $_GET['type'] === 'experiments') {
        $tb = 'exp';
        $tbt = 'exptag';
    } else {
        $tb = 'i';
        $tbt = 'itag';
    }

    $sqlGroup = " GROUP BY $tb.id";

    // Title search
    if ($titleWithSpace) {
        $sqlTitle = " AND (";
        foreach ($title_arr as $key => $value) {
            if ($key != 0) {
                $sqlTitle .= $andor;
            }
            $sqlTitle .= "$tb.title LIKE '%$value%'";
        }
        $sqlTitle .= ")";
    } elseif (!empty($title)) {
        $sqlTitle = " AND $tb.title LIKE '%$title%'";
    } else {
        $sqlTitle = "";
    }

    // Body search
    if ($bodyWithSpace) {
        $sqlBody = " AND (";
        foreach ($body_arr as $key => $value) {
            if ($key != 0) {
                $sqlBody .= $andor;
            }
            $sqlBody .= "$tb.body LIKE '%$value%'";
        }
        $sqlBody .= ")";
    } elseif (!empty($body)) {
        $sqlBody = " AND $tb.body LIKE '%$body%'";
    } else {
        $sqlBody = "";
    }

    // Tag search
    if (!empty($tags)) {
        $sqlTag = " AND $tb.id = $tbt.item_id AND $tbt.tag = '$tags'";
    } else {
        $sqlTag = "";
    }

    // Status search
    if (!empty($status)) {
        $sqlStatus = " AND $tb.status LIKE '$status'";
    } else {
        $sqlStatus = "";
    }

    // Rating search
    if (!empty($rating)) {
        $sqlRating = " AND $tb.rating LIKE '$rating'";
    } else {
        $sqlRating = "";
    }

    // Date search
    if (!empty($from) && !empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '$from' AND '$to'";
    } elseif (!empty($from) && empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '$from' AND '99991212'";
    } elseif (empty($from) && !empty($to)) {
        $sqlDate = " AND $tb.date BETWEEN '00000101' AND '$to'";
    } else {
        $sqlDate = "";
    }

    // EXPERIMENT SEARCH
    if (isset($_GET['type'])) {
        if ($_GET['type'] === 'experiments') {

            if (isset($_GET['owner']) && $_GET['owner'] === '0') {
                $sqlFirst = " $tb.team = " . $_SESSION['team_id'];
            } else {
                $sqlFirst = " $tb.userid = :userid";
            }

            // if you select from two tables but one is empty, as it makes a cross join, no results will be returned
            // on a fresh install, if there is no tags, it will not find anything
            // so we make a left join
            // https://stackoverflow.com/questions/3171276/select-multiple-tables-when-one-table-is-empty-in-mysql
            $sql = "SELECT exp.* FROM experiments as exp LEFT JOIN experiments_tags as exptag ON 1=1 WHERE" . $sqlFirst . $sqlTitle . $sqlBody . $sqlTag . $sqlStatus . $sqlDate . $sqlGroup;
            $req = $pdo->prepare($sql);
            // if there is a selection on 'owned by', we use the owner id as parameter
            if ($owner_search) {
                $req->execute(array(
                    'userid' => $owner
                ));
            } else {
                $req->execute(array(
                    'userid' => $_SESSION['userid']
                ));
            }

            $search_type = 'experiments';

        // DATABASE SEARCH
        } elseif (Tools::checkId($_GET['type']) || $_GET['type'] === 'database') {
            // we want only stuff from our team
            $sqlTeam = " AND i.team = " . $_SESSION['team_id'];

            // display entire team database
            if ($_GET['type'] === 'database' &&
                empty($title) &&
                empty($body) &&
                empty($tags) &&
                empty($status) &&
                empty($rating) &&
                empty($from) &&
                empty($to)) {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN items_tags as itag ON 1=1 WHERE i.id > 0";

            } elseif ($_GET['type'] === 'database') {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN items_tags as itag ON 1=1 WHERE i.id > 0";

            } else {

                $sqlFirst = "SELECT i.* FROM items as i LEFT JOIN  items_tags as itag ON 1=1 WHERE type = :type";
            }

            $sql = $sqlFirst . $sqlTeam . $sqlTitle . $sqlBody . $sqlTag . $sqlRating . $sqlDate . $sqlGroup;
            $req = $pdo->prepare($sql);
            if ($_GET['type'] === 'database') {
                $req->execute();
            } else {
                $req->execute(array(
                    'type' => $_GET['type']
                ));
            }

            $search_type = 'items';
        }

        // BEGIN DISPLAY RESULTS

        if ($req->rowCount() === 0) {
                display_message('ko_nocross', _("Sorry. I couldn't find anything :("));
        } else {
            while ($get_id = $req->fetch()) {
                $results_arr[] = $get_id['id'];
            }
            // sort by id, biggest (newer item) comes first
            $results_arr = array_reverse($results_arr);
            $total_time = get_total_time();
            ?>
            <!-- Export CSV/ZIP -->
            <div class='align_right'>
                <a name='anchor'></a>
                <p class='inline'><?= _('Export this result:') ?> </p>
                <a href='make.php?what=zip&id=<?= Tools::buildStringFromArray($results_arr) ?>&type=<?= $search_type ?>'>
                    <img src='img/zip.png' title='make a zip archive' alt='zip' />
                </a>

                <a href='make.php?what=csv&id=<?= Tools::buildStringFromArray($results_arr) ?>&type=<?= $search_type ?>'>
                    <img src='img/spreadsheet.png' title='Export in spreadsheet file' alt='Export CSV' />
                </a>
            </div>
            <?php
            echo "<p>" . count($results_arr) . " " . ngettext("result found", "results found", count($results_arr)) . " (" . $total_time['time'] . " " . $total_time['unit'] . ")</p>";
            // Display results
            echo "<hr>";
            if ($search_type === 'experiments') {
                $EntityView = new ExperimentsView(new Experiments($_SESSION['team_id'], $_SESSION['userid']));
            } else {
                $EntityView = new DatabaseView(new Database($_SESSION['team_id']), $_SESSION['userid']);
            }

            foreach ($results_arr as $id) {
                if ($search_type === 'experiments') {
                    $EntityView->Experiments->id = $id;
                    $item = $EntityView->Experiments->read();
                } else {
                    $EntityView->Database->id = $id;
                    $item = $EntityView->Database->read();
                }

                echo $EntityView->showUnique($item);
            }
        }
    }
}
?>

<script>
$(document).ready(function(){
    // DATEPICKER
    $( ".datepicker" ).datepicker({dateFormat: 'yymmdd'});
    <?php
    // I added !isset(get[type]) to avoid showing tab_db if we just got to the page
    if ((isset($_GET['type']) && $_GET['type'] == 'experiments') || !isset($_GET['type'])) {
        echo '$("#tag_db").hide();';
    } else {
        echo '$("#tag_exp").hide();';
    }
    ?>

    $('#searchonly').on('change', function() {
        insertParamAndReload('owner', $(this).val());
    });

    $('#searchin').on('change', function() {
        if (this.value == 'experiments') {
            $("#tag_exp").show();
            $("#tag_db").hide();
        } else {
            $("#tag_exp").hide();
            $("#tag_db").show();
        }
    });

<?php
// scroll to anchor if there is a search
if (isset($_GET)) {
    echo "location.hash = '#anchor';";
}?>
});
</script>

<?php require_once('app/footer.inc.php');
