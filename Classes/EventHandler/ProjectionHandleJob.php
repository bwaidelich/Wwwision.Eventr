<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Jobqueue\Common\Job\JobInterface;
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Eventr;

/**
 */
class ProjectionHandleJob implements JobInterface
{

	/**
	 * @var string
	 */
	protected $projectionName;

	/**
	 * @var string
	 */
	protected $aggregateType;

	/**
	 * @var string
	 */
	protected $aggregateId;

	/**
	 * @var EventInterface
	 */
	protected $event;

	/**
	 * @Flow\Inject
	 * @var Eventr
	 */
	protected $eventr;

	/**
	 * @param string $projectionName
	 * @param string $aggregateType
	 * @param string $aggregateId
	 * @param EventInterface $event
	 */
	public function __construct($projectionName, $aggregateType, $aggregateId, EventInterface $event) {
		$this->projectionName = $projectionName;
		$this->aggregateType = $aggregateType;
		$this->aggregateId = $aggregateId;
		$this->event = $event;
	}

	/**
	 * Execute the job
	 * A job should finish itself after successful execution using the queue methods.
	 *
	 * @param QueueInterface $queue
	 * @param Message $message The original message
	 * @return boolean TRUE if the job was executed successfully and the message should be finished
	 */
	public function execute(QueueInterface $queue, Message $message) {
		$projection = $this->eventr->getProjection($this->projectionName);
		$aggregateType = $this->eventr->getAggregateType($this->aggregateType);
		$aggregate = $aggregateType->getAggregate($this->aggregateId);
		$projection->handle($aggregate, $this->event);
		return TRUE;
	}

	/**
	 * @return string A job identifier
	 */
	public function getIdentifier() {
		return NULL;
	}

	/**
	 * @return string A label for the job
	 */
	public function getLabel() {
		return sprintf('ProjectionHandleJob for projection "%s", event "%s.%s" @ %s', $this->projectionName, $this->aggregateType, $this->event->getType(), $this->aggregateId);
	}
}