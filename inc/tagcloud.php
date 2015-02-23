<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
echo "<section class='box'>";
echo "<img src='img/cloud.png' alt='' /> <h4>"._('Tag cloud')."</h4>";
echo "<div id='tagcloud'>";
// 1. Create an array with tag -> count
$sql = "SELECT tag, COUNT(id) AS total
    FROM experiments_tags
    WHERE userid = :userid
    GROUP BY tag ORDER BY total DESC";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();
$full = $req->fetchAll();
$count = count($full);
// need at least 10 tags to make a cloud
if ($count > 10) {
    // max occurence = first result in array
    $maxoccur = $full[0][1];
    // min occurenc = last result in array
    $minoccur = $full[$count-1][1];

    // 2nd SQL to get the tags unsorted
    $sql = "SELECT tag, COUNT(id) AS total FROM experiments_tags WHERE userid = ".$_SESSION['userid']." GROUP BY tag";
    $req = $pdo->prepare($sql);
    $req->execute();
    $spread = $maxoccur - $minoccur;
    if ($spread === 0) {
        $spread = 1;
    }
    while ($data = $req->fetch()) {
        // Calculate ratio
        $ratio = floor((($data[1] - $minoccur) / $spread)*100);
        if ($ratio < 10) {
            $class = 'c1';
        } elseif ($ratio >= 10 && $ratio < 20) {
            $class = 'c2';
        } elseif ($ratio >= 20 && $ratio < 30) {
            $class = 'c3';
        } elseif ($ratio >= 30 && $ratio < 40) {
            $class = 'c4';
        } elseif ($ratio >= 40 && $ratio < 50) {
            $class = 'c5';
        } elseif ($ratio >= 50 && $ratio < 60) {
            $class = 'c6';
        } elseif ($ratio >= 60 && $ratio < 70) {
            $class = 'c7';
        } elseif ($ratio >= 70 && $ratio < 80) {
            $class = 'c8';
        } elseif ($ratio >= 80 && $ratio < 90) {
            $class = 'c9';
        } else {
            $class = 'c10';
        }
        echo "<a href='experiments.php?mode=show&q=".$data[0]."' class='".$class."'>".stripslashes($data[0])."</a> ";
    }
    // TAGCLOUD
    echo "</div>";
} else {
    echo _('Not enough tags to make a tagcloud.');
}// end fix division by zero
?>
</section>
