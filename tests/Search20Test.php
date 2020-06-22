<?php declare(strict_types=1);

namespace Keyman\Site\com\keyman\api\tests {

  require_once(__DIR__ . '/../tools/base.inc.php');
  require_once(__DIR__ . '/../script/search/2.0/search.inc.php');
  require_once(__DIR__ . '/TestUtils.inc.php');
  require_once(__DIR__ . '/TestDBBuild.inc.php');

  use PHPUnit\Framework\TestCase;

  final class Search20Test extends TestCase
  {
    private const SchemaFilename = "/search/2.0/search.json";

    private $schema, $mssql, $s;

    static function setUpBeforeClass(): void
    {
      TestDBBuild::Build();
    }

    public function setUp(): void
    {
      $this->schema = TestUtils::LoadJSONSchema(Search20Test::SchemaFilename);
      $this->mssql = \Keyman\Site\com\keyman\api\Tools\DB\DBConnect::Connect();
      $this->s = new \KeyboardSearch($this->mssql);
    }

    public function testSimpleSearchResultValidatesAgainstSchema(): void
    {
      $json = $this->s->GetSearchMatches(null, 'thai', 1);

      // Whoa, PHP does *not* round-trip JSON cleanly. This however takes our output and transforms it
      // to something that passes our schema validation
      // TODO(lowpri): find a way to skip this by emitting clean JSON object from WriteSearchResults()
      $json = json_decode(json_encode($json));

      // This will throw an exception if it does not pass
      $this->schema->in($json);

      // Once we get here we know this test has passed so make PHPUnit happy
      $this->assertTrue(true);
    }

    public function testSimpleSearchResultContentsConsistent()
    {
      $json = $this->s->GetSearchMatches(null, 'khmer', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/Search.2.0.khmer.json', json_encode($json), "Search for 'khmer' gives same results as Search.2.0.khmer.json");
    }

    public function testPhraseSearchResult()
    {
      $json = $this->s->GetSearchMatches(null, 'khmer angkor', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/Search.2.0.khmer-angkor.json', json_encode($json), "Search for 'khmer angkor' gives same results as Search.2.0.khmer-angkor.json");
    }

    public function testUnicodeSearchResult()
    {
      $json = $this->s->GetSearchMatches(null, 'ት', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/Search.2.0.ethiopic.json', json_encode($json), "Search for 'ት' gives same results as Search.2.0.ethiopic.json");
    }

    public function testKeyboardIdSearchResult()
    {
      $json = $this->s->GetSearchMatches(null, 'khmer_', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('khmer_angkor', $json->keyboards[0]->id);
    }

    public function testDinkaSearchResult() {
      $json = $this->s->GetSearchMatches(null, 'Thuɔŋjäŋ', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(3, $json->context->totalRows);
      $this->assertEquals('el_dinka', $json->keyboards[0]->id);
      $this->assertEquals('dlia25bas', $json->keyboards[1]->id);
      $this->assertEquals('dinkaweb11', $json->keyboards[2]->id);
    }

  }
}
