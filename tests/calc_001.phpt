--TEST--
Simple stackless calc
--SKIPIF--
<?php if (!extension_loaded("parle")) print "skip"; ?>
--FILE--
<?php 

use Parle\Parser;
use Parle\ParserException;
use Parle\RLexer;

$p = new Parser;
$p->token("INTEGER");
$p->left("'+' '-' '*' '/'");

$p->push("start", "exp");
$add_idx = $p->push("exp", "exp '+' exp");
$sub_idx = $p->push("exp", "exp '-' exp");
$mul_idx = $p->push("exp", "exp '*' exp");
$div_idx = $p->push("exp", "exp '/' exp");
$int_idx = $p->push("exp", "INTEGER");

$p->build();

$lex = new RLexer;
$lex->push("[+]", $p->tokenId("'+'"));
$lex->push("[-]", $p->tokenId("'-'"));
$lex->push("[*]", $p->tokenId("'*'"));
$lex->push("[/]", $p->tokenId("'/'"));
$lex->push("\\d+", $p->tokenId("INTEGER"));
$lex->push("\\s+", $lex->skip());

$lex->build();

$exp = array(
	"1 + 1",
	"33 / 10",
	"100 * 45",
	"17 - 45",
);

foreach ($exp as $in) {
	if (!$p->validate($in, $lex)) {
		throw new ParserException("Failed to validate input");
	}

	$p->consume($in, $lex);

	$act = $p->action();

	while (Parser::ACTION_ERROR != $act && Parser::ACTION_ACCEPT != $act) {
		switch ($act) {
			case Parser::ACTION_ERROR:
				throw new ParserException("Parser error");
				break;
			case Parser::ACTION_SHIFT:
			case Parser::ACTION_GOTO:
			case Parser::ACTION_ACCEPT:
				break;
			case Parser::ACTION_REDUCE:
				$rid = $p->reduceId();

				/*$l = $p->sigil(0);
				$r = $p->sigil(2);*/

				switch ($rid) {
					case $add_idx:
						$l = $p->sigil(0);
						$r = $p->sigil(2);
						echo "$l + $r = " . ($l + $r) . "\n";
						break;
					case $sub_idx:
						$l = $p->sigil(0);
						$r = $p->sigil(2);
						echo "$l - $r = " . ($l - $r) . "\n";
						break;
					case $mul_idx:
						$l = $p->sigil(0);
						$r = $p->sigil(2);
						echo "$l * $r = " . ($l * $r) . "\n";
						break;
					case $div_idx:
						$l = $p->sigil(0);
						$r = $p->sigil(2);
						echo "$l / $r = " . ($l / $r) . "\n";
						break;
				}
			break;
		}
		$p->advance();
		$act = $p->action();
	}
}

?>
==DONE==
--EXPECT--
1 + 1 = 2
33 / 10 = 3.3
100 * 45 = 4500
17 - 45 = -28
==DONE==
