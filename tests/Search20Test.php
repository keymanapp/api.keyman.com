<?php declare(strict_types=1);

namespace {
  require_once(__DIR__ . '/../tools/base.inc.php');
  require_once(__DIR__ . '/../script/search/2.0/search.inc.php');
  require_once(__DIR__ . '/TestUtils.inc.php');
  require_once(__DIR__ . '/TestDBBuild.inc.php');
}

namespace Keyman\Site\com\keyman\api\tests {

  use PHPUnit\Framework\TestCase;

  final class Search20Test extends TestCase
  {
    private const SchemaFilename = "/search/2.0/search.json";

    static function setUpBeforeClass(): void
    {
      TestDBBuild::Build();
    }

    public function testSimpleSearchResultValidatesAgainstSchema(): void
    {
      $schema = TestUtils::LoadJSONSchema(Search20Test::SchemaFilename);
      $mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();

      $s = new \KeyboardSearch($mssql);
      $json = $s->GetSearchMatches('keyboard', null, 'thai', 1);

      // Whoa, PHP does *not* round-trip JSON cleanly. This however takes our output and transforms it
      // to something that passes our schema validation
      // TODO(lowpri): find a way to skip this by emitting clean JSON object from WriteSearchResults()
      $json = json_decode(json_encode($json));

      // TOOD: hmm, this schema seems to be fairly lax! It shouldn't be passing...

      // This will throw an exception if it does not pass
      $schema->in($json);

      // Once we get here we know this test has passed so make PHPUnit happy
      $this->assertTrue(true);
    }

    public function testSimpleSearchResultContentsConsistent()
    {
      $mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();
      $s = new \KeyboardSearch($mssql);
      $json = $s->GetSearchMatches('keyboard', null, 'khmer', 1);
      $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/Search.2.0.khmer.json', json_encode($json), "Search for 'khmer' gives same results as Search.khmer.json");
    }
  }
}
