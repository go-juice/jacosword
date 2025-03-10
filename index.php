<?php
session_start();

// Sanitize timezone parameter to prevent injection attacks
if (isset($_GET['timezone'])) {
    // Validate the timezone to ensure it's a valid one (you can extend this list if needed)
    $valid_timezones = DateTimeZone::listIdentifiers();
    if (in_array($_GET['timezone'], $valid_timezones)) {
        $_SESSION['time'] = $_GET['timezone'];
    }
}

// Default to a common timezone if session is empty
if (!isset($_SESSION['time']) || empty($_SESSION['time'])) {
    $_SESSION['time'] = 'Pacific/Auckland';  // Change this to your actual local timezone
}

$timezone = $_SESSION['time'];

// Set the endpoint URL of your Google Apps Script Web App
$scriptUrl = 'https://script.google.com/macros/s/AKfycbzPJellfuPPxHWzsiL2nLq1_BE5SxgQ3YvdyGDEdZ3Xz28YAUu6PMSi7dOJj51RRaBH/exec';

// Get the data from the Google Apps Script API (your web app endpoint)
$response = file_get_contents($scriptUrl);
$data = json_decode($response, true);


// Get today's date (local time based on user's timezone)
$today = new DateTime();
$today->setTimezone(new DateTimeZone($timezone)); // User's timezone
$todayString = $today->format('m/d/Y');

// Filter data to show only today's results
$filteredData = array_filter($data, function($entry) use ($todayString, $timezone) {
    // Parse the timestamp in UTC and convert to the user's timezone
    $entryDate = new DateTime($entry['timestamp'], new DateTimeZone('UTC')); // Parse as UTC
    $entryDate->setTimezone(new DateTimeZone($timezone)); // Convert to user timezone

    // Compare only the date part (ignore the time)
    return $entryDate->format('m/d/Y') === $todayString;
});
// Initialize the final sorted array
$finalSortedArray = [];

// Iterate through scores from 1 to 6
for ($score = 1; $score <= 6; $score++) {
    // Filter out entries with the current score
    $filteredByScore = array_filter($filteredData, function($entry) use ($score) {
        return $entry['guesses'] == $score;
    });

    // Sort the filtered entries by timestamp (oldest to newest)
    usort($filteredByScore, function($a, $b) {
        $aDate = new DateTime($a['timestamp'], new DateTimeZone('UTC'));
        $bDate = new DateTime($b['timestamp'], new DateTimeZone('UTC'));
        return $aDate <=> $bDate; // Compare timestamps (oldest first)
    });

    // Merge the sorted results into the final array
    $finalSortedArray = array_merge($finalSortedArray, $filteredByScore);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Starting word generator - Wordle</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      html, body, div, h1, p { margin: 0; padding: 0; }
      html, body {
        height: 100%;
      }
      body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        font: normal 24px/36px 'Clear Sans', 'Helvetica Neue', Arial, sans-serif;
        color: #333;
      }
      h1 {
        font-size: 24px;
        line-height: 36px;
        margin-bottom: 24px;
        text-align: center;
        margin-top: 24px;
      }
      #wrap {
        margin-top: auto;
        margin-bottom: auto;
        padding: 40px;
      }
      #main {
        text-align: center;
        margin-bottom: 40px;
      }
      button {
        line-height: 54px;
        height: 60px;
        border: 3px solid #6aaa64;
        background-color: #6aaa64;
        background-image: linear-gradient(#75c775, #6aaa64);
        text-shadow: -1px -1px 0 rgb(0 0 0 / 15%);
        border-radius: 3px;
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        padding: 0 30px;
        box-sizing: border-box;
      }
      .boxes {
        display: flex;
        justify-content: center;
      }
      .box {
        width: 58px;
        height: 58px;
        line-height: 62px;
        background: #fff;
        border: 2px solid #d3d6da;
        text-transform: uppercase;
        font-size: 32px;
        font-weight: bold;
        margin-left: 2px;
        margin-right: 2px;
      }
      .box[data-color="green"] {
        background: #6aaa64;
        border-color: #6aaa64;
      }
      .box[data-color="yellow"] {
        background: #c9b458;
        border-color: #c9b458;
      }
      #again {
        margin-top: 36px;
        color: #d3d6da;
      }
      #again a {
        text-decoration: none;
        padding-left: 4px;
        padding-right: 4px;
      }
      #copy,
      #wordle {
        color: #6aaa64;
      }
      #reset {
        color: #c9b458;
      }
      #msg-wrap {
        position: fixed;
        top: 100px;
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
      }
      #msg,
      #lmgtfy {
        background: #333;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 18px;
        line-height: 24px;
      }
      footer {
        margin-top: 36px;
        margin-bottom: 24px;
        text-align: center;
      }
      footer a {
        color: #999;
        text-decoration: none;
      }
      .hide {
        display: none;
      }
      .hidden {
        visibility: hidden;
      }

      /* Leaderboard */
      table {
          width: 50%;
          margin: 20px auto;
          border-collapse: collapse;
      }
      th, td {
          padding: 10px;
          border: 1px solid #ddd;
          text-align: left;
      }
      th {
          background-color: #f4f4f4;
      }
      .bold {
          font-weight: bold;
      }
      .rank-1 { background-color: #6aaa64; color: white; } /* Wordle Green */
      .rank-2 { background-color: #c9b458; color: white; } /* Wordle Yellow */
      .rank-3 { background-color: #787c7e; color: white; } /* Wordle Grey */
  </style>
  <script type="text/javascript" src="https://code.jquery.com/jquery-latest.min.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
        if (!document.cookie.includes("timezoneSet")) {
            var visitortimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
          	console.log(visitortimezone);
            $.ajax({
                type: "GET",
                url: "index.php",
                data: 'timezone=' + visitortimezone,
                success: function() {
                    document.cookie = "timezoneSet=true; path=/"; // Prevent multiple requests
                    location.reload();
                }
            });
        }
    });
  </script>
</head>
<body>
  <h1>Starting word generator</h1>
  <div id="wrap">
    <div id="main">
      <div id="gen">
        <button id="generate">Generate word</button>
      </div>
      <div id="result" class="hide">
        <div class="boxes">
          <div class="box"></div>
          <div class="box"></div>
          <div class="box"></div>
          <div class="box"></div>
          <div class="box"></div>
        </div>
      </div>
      
      <div id="again" class="hidden">
        <a href="#" id="copy">Copy</a> | <a href="#" id="reset">Again</a> | <a id="wordle" href="https://www.nytimes.com/games/wordle/index.html" target="_blank" rel="noopener noreferrer">Wordle</a>
        <div>
          <a href="#" id="lmgtfy">What is this word?</a>
        </div>
      </div>
    </div>
  </div>
  <div id="msg-wrap">
    <div id="msg" class="hide"></div>
  </div>
  <footer>
    <h1>Leaderboard</h1>
    <table class="leaderboard">
    <thead>
        <tr>
            <th>Rank</th>
            <th>Nickname</th>
            <th>Score</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Display the leaderboard
        $rank = 1;
        foreach ($finalSortedArray as $index => $entry) {
          $class = '';
          // Highlight the top 3 entries
          if ($rank == 1) {
              $class = 'rank-1';
          } elseif ($rank == 2) {
              $class = 'rank-2';
          } elseif ($rank == 3) {
              $class = 'rank-3';
          }
          // Sanitize nickname for output in HTML
          $nickname = htmlspecialchars($entry['nickname'], ENT_QUOTES, 'UTF-8');
          echo "<tr class='{$class}'>
                  <td>$rank</td>
                  <td class='nickname'>{$nickname}</td>
                  <td>{$entry['guesses']}</td>
                </tr>";
          $rank++;
      }
        ?>
    </tbody>
</table>
  </footer>
  <script src="main.js"></script>
</body>
</html>
