<?php
namespace Wwwision\Eventr\Command;

use EventStore\ExpectedVersion;
use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Model\Projection;
use Wwwision\Eventr\Eventr;
use Wwwision\Eventr\PdoProjectionAdapter;
use Yeebase\Gurumanage\Domain\Repository\PersonRepository;

/**
 * @Flow\Scope("singleton")
 */
class TestCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var PersonRepository
     */
    protected $personRepository;

    /**
     * @Flow\Inject
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\Inject
     * @var Eventr
     */
    protected $eventr;

    /**
     * @return void
     */
    public function initCommand()
    {
        $commentType = $this->eventr->registerAggregateType('Comment');
        $commentType->registerEventType('created', json_decode('{"date": {"type": "string", "format": "date-time", "required": true}, "author": {"type": "string", "required": true, "minLength": 3}, "text": {"type": "string", "minLength": 3}}', true));
        $commentType->registerEventType('hidden');
        $commentType->registerEventType('updated', json_decode('{"newText": {"type": "string", "required": true, "minLength": 3}}', true));

        $mapping = [
            'created' => [
                'createdAt' => 'event.data.date',
                'author' => 'event.data.author',
                'text' => 'event.data.text',
                'hidden' => 'false'
            ],
            'updated' => [
                'text' => 'event.data.newText',
            ],
            'hidden' => [
                'hidden' => 'true',
            ],
        ];
        $projection = new Projection('CommentList', $commentType, $mapping, ['className' => PdoProjectionAdapter::class, 'options' => ['tableName' => 'comments']]);
        $this->eventr->registerProjection($projection);

        $this->outputLine('done.');
    }

    /**
     * @param string $author
     * @param string $text
     * @return void
     */
    public function createCommentCommand($author, $text)
    {
        $payload = ['date' => (new \DateTimeImmutable())->format(DATE_ISO8601), 'author' => $author, 'text' => $text];
        $commentId = AggregateId::create();
        $this->eventr->getAggregate('Comment', $commentId)->emitEvent('created', $payload, ExpectedVersion::NO_STREAM);

        $this->outputLine('Created comment "%s"', [$commentId]);
    }

    /**
     * @param string $id
     * @param string $newText
     * @return void
     */
    public function updateCommentCommand($id, $newText)
    {
        $payload = ['newText' => $newText];
        $this->eventr->getAggregate('Comment', new AggregateId($id))->emitEvent('updated', $payload);

        $this->outputLine('Updated comment "%s"', [$id]);
    }

    /**
     * @param string $id
     * @return void
     */
    public function hideCommentCommand($id)
    {
        $this->eventr->getAggregate('Comment', new AggregateId($id))->emitEvent('hidden');

        $this->outputLine('Hid comment "%s"', [$id]);
    }
}