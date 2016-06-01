<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Utility\SchemaValidator;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Eventr;

/**
 * @Flow\Scope("singleton")
 */
class VerifyPayloadEventHandler implements EventHandlerInterface
{

    /**
     * @Flow\Inject
     * @var SchemaValidator
     */
    protected $schemaValidator;

    /**
     * @Flow\Inject
     * @var Eventr
     */
    protected $eventr;

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        $eventType = $aggregate->getType()->getEventType($event->getType());
        $payload = $event->getData();
        if (!$eventType->hasSchema()) {
            if ($payload !== []) {
                throw new \InvalidArgumentException(sprintf('No payload is expected for EventType "%s"', $eventType), 1456505193);
            }
            return;
        }
        $schema = ['type' => 'dictionary', 'additionalProperties' => false, 'properties' => $eventType->getSchema()];
        $result = $this->schemaValidator->validate($payload, $schema);
        if (!$result->hasErrors()) {
            return;
        }
        $message = '';
        foreach ($result->getFlattenedErrors() as $propertyPath => $errors) {
            $message .= sprintf('"%s":' . chr(10), $propertyPath);
            /** @var Error $error */
            foreach ($errors as $error) {
                $message .= ' ' . $error->render() . chr(10);
            }
        }
        throw new \InvalidArgumentException(sprintf('Payload does not adhere to schema of EventType "%s"%s%s', $eventType, chr(10), $message), 1456504367);
    }

}