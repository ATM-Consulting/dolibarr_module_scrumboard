<?php

use PHPUnit\Framework\TestCase;

class ScrumboardIncludesTest extends TestCase
{
	private const SYMBOLS = [
		'/\bproject_prepare_head\(/' => '/core/lib/project.lib.php',
		'/new Project\(/' => '/projet/class/project.class.php',
		'/new Task\(/' => '/projet/class/task.class.php',
		'/new Societe\(/' => '/societe/class/societe.class.php',
		'/new ExtraFields\(/' => '/core/class/extrafields.class.php',
		'/new FormCompany\(/' => '/core/class/html.formcompany.class.php',
		'/\bconvertSecondToTime\(/' => '/core/lib/date.lib.php',
	];

	/** @return array<string, array{string}> */
	public static function entryPoints(): array
	{
		return [
			'board page' => ['scrum.php'],
			'board service' => ['script/interface.php'],
			'library' => ['lib/scrumboard.lib.php'],
		];
	}

	/**
	 * @dataProvider entryPoints
	 */
	public function testEveryCoreSymbolUsedIsRequiredByTheFileItself(string $file): void
	{
		$source = file_get_contents(dirname(__DIR__, 2).'/'.$file);
		$this->assertNotFalse($source);

		foreach (self::SYMBOLS as $usage => $include) {
			if (preg_match($usage, $source)) {
				$this->assertStringContainsString($include, $source, $file.' uses '.$usage.' without requiring '.$include);
			}
		}
	}

	public function testBoardPageLoadsTheCoreLanguageFilesItDisplays(): void
	{
		$source = file_get_contents(dirname(__DIR__, 2).'/scrum.php');
		$this->assertMatchesRegularExpression('/loadLangs\(array\([^)]*\'projects\'[^)]*\'companies\'/', $source);
	}
}
