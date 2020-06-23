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

    // Searches with qualifiers, e.g. k: l: c: s: id: legacy:

    public function testSearchByKeyboard()
    {
      $json = $this->s->GetSearchMatches(null, 'k:khmer', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertJsonStringEqualsJsonFile(__DIR__ . '/fixtures/Search.2.0.khmer-keyboards.json', json_encode($json), "Search for 'k:khmer' gives same results as Search.2.0.khmer-keyboards.json");
    }

    public function testSearchByKeyboardId()
    {
      $json = $this->s->GetSearchMatches(null, 'id:basic_kbdkhmr', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('basic_kbdkhmr', $json->keyboards[0]->id);
      $this->assertEquals('keyboard_id', $json->keyboards[0]->match->type);

      $json = $this->s->GetSearchMatches(null, 'k:id:basic_kbdkhmr', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('basic_kbdkhmr', $json->keyboards[0]->id);
      $this->assertEquals('keyboard_id', $json->keyboards[0]->match->type);
    }

    public function testSearchByLegacyKeyboardId()
    {
      $json = $this->s->GetSearchMatches(null, 'legacy:681', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('acoli', $json->keyboards[0]->id);
      $this->assertEquals('legacy_keyboard_id', $json->keyboards[0]->match->type);

      $json = $this->s->GetSearchMatches(null, 'k:legacy:681', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('acoli', $json->keyboards[0]->id);
      $this->assertEquals('legacy_keyboard_id', $json->keyboards[0]->match->type);
    }

    public function testSearchByLanguageBcp47Tag()
    {
      $json = $this->s->GetSearchMatches(null, 'l:id:ach', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('acoli', $json->keyboards[0]->id);
      $this->assertEquals('language_bcp47_tag', $json->keyboards[0]->match->type);

      $json = $this->s->GetSearchMatches(null, 'l:id:km-Khmr-KH', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(6, $json->context->totalRows);
      $this->assertEquals('km', $json->keyboards[0]->match->name);
      $this->assertEquals('language_bcp47_tag', $json->keyboards[0]->match->type);
      $this->assertEquals('khmer_angkor', $json->keyboards[0]->id);
      $this->assertEquals('basic_kbdkni', $json->keyboards[1]->id);
      $this->assertEquals('sil_khmer', $json->keyboards[2]->id);
      $this->assertEquals('basic_kbdkhmr', $json->keyboards[3]->id);
      $this->assertEquals('kbdkhmr', $json->keyboards[4]->id);
      $this->assertEquals('khmer10', $json->keyboards[5]->id);
    }

    public function testSearchByLanguageName()
    {
      $json = $this->s->GetSearchMatches(null, 'l:Blang', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('basic_kbdtaile', $json->keyboards[0]->id);
    }

    public function testSearchByCountryIso3166Code()
    {
      $json = $this->s->GetSearchMatches(null, 'c:id:nz', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(5, $json->context->totalRows);
      $this->assertEquals('el_pasifika', $json->keyboards[0]->id);
      $this->assertEquals('country_iso3166_code', $json->keyboards[0]->match->type);
    }

    public function testSearchByCountryName()
    {
      $json = $this->s->GetSearchMatches(null, 'c:Tanzania', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(6, $json->context->totalRows);
      $this->assertEquals('sil_uganda_tanzania', $json->keyboards[0]->id);
    }

    public function testSearchByScriptIso15924Code()
    {
      $json = $this->s->GetSearchMatches(null, 's:id:bali', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(1, $json->context->totalRows);
      $this->assertEquals('aksarabali_panlex', $json->keyboards[0]->id);
      $this->assertEquals('script_iso15924_code', $json->keyboards[0]->match->type);
    }

    public function testSearchByScriptName()
    {
      $json = $this->s->GetSearchMatches(null, 's:Ethiopic', 1);
      $json = json_decode(json_encode($json));
      $this->schema->in($json);
      $this->assertEquals(42, $json->context->totalRows);
      $this->assertEquals('sil_ethiopic', $json->keyboards[0]->id);
    }
  }
}
