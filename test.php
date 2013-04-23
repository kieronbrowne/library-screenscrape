<?php

$data = file_get_contents("./data");

#preg_match_all('/TitleListResultsCenterStyle4Reduced.*class="cb"/Us', $data, $matches);
preg_match_all('/TitleListResultsCenterStyle4Reduced.*<h2><a[^>]*>([^<]*)<.*<h2>By ([^<]*)<\/h2>.*<strong>Due Date:<\/strong>(\d{1,2}\/\d{1,2}\/\d{2})/Us', $data, $matches);
for ($i = 0; $i < count($matches[0]); $i++) {
	$book = trim($matches[1][$i]);
	$author = trim($matches[2][$i]);
	$dueDate = date_create_from_format('d/m/y', $matches[3][$i]);
	$daysRemaining = -$dueDate->diff(new DateTime())->format('%r%a');
	printf("%s (%s) due back in %s days\n", $book, $author, $daysRemaining);
}

