<?php declare(strict_types=1);

namespace Shopware\Storefront\Test;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Term\SearchTerm;
use Shopware\Core\Framework\Search\Util\KeywordSearchTermInterpreterInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class KeywordSearchTermInterpreterTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var KeywordSearchTermInterpreterInterface
     */
    private $interpreter;

    protected function setUp(): void
    {
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->interpreter = $this->getContainer()->get(KeywordSearchTermInterpreterInterface::class);
        $this->setupKeywords();
    }

    /**
     * @dataProvider cases
     *
     * @param string $term
     * @param array  $expected
     */
    public function testMatching(string $term, array $expected): void
    {
        $context = Context::createDefaultContext();

        $matches = $this->interpreter->interpret($term, 'product', $context);

        $keywords = array_map(function (SearchTerm $term) {
            return $term->getTerm();
        }, $matches->getTerms());

        sort($expected);
        sort($keywords);
        static::assertEquals($expected, $keywords);
    }

    public function cases(): array
    {
        return [
            [
                'zeichn',
                ['zeichnet', 'zeichen', 'zweichnet'],
            ],
            [
                'zeichent',
                ['ausgezeichnet', 'gezeichnet', 'zeichnet'],
            ],
            [
                'Büronetz',
                ['büronetzwerk'],
            ],
        ];
    }

    private function setupKeywords(): void
    {
        $keywords = [
            'zeichnet',
            'zweichnet',
            'ausgezeichnet',
            'verkehrzeichennetzwerk',
            'gezeichnet',
            'zeichen',
            'zweideutige',
            'zweier',
            'zweite',
            'zweiteilig',
            'zweiten',
            'zweites',
            'zweiweg',
            'zweifellos',
            'büronetzwerk',
            'heimnetzwerk',
            'netzwerk',
            'netzwerkadapter',
            'netzwerkbuchse',
            'netzwerkcontroller',
            'netzwerkdrucker',
            'netzwerke',
            'netzwerken',
            'netzwerkinfrastruktur',
            'netzwerkkabel',
            'netzwerkkabels',
            'netzwerkkarte',
            'netzwerklösung',
            'netzwerkschnittstelle',
            'netzwerkschnittstellen',
            'netzwerkspeicher',
            'netzwerkspeicherlösung',
            'netzwerkspieler',
            'schwarzweiß',
            'netzwerkprotokolle',
        ];

        $languageId = Uuid::fromString(Defaults::LANGUAGE_SYSTEM)->getBytes();

        foreach ($keywords as $keyword) {
            preg_match_all('/./us', $keyword, $ar);

            $this->connection->insert('search_dictionary', [
                'scope' => 'product',
                'keyword' => $keyword,
                'reversed' => implode('', array_reverse($ar[0])),
                'language_id' => $languageId,
            ]);
        }
    }
}
