<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Entity(readOnly=TRUE)
 * @Flow\Proxy(false)
 * @ORM\Table(name="eventr_events", uniqueConstraints={@ORM\UniqueConstraint(name="stream_version", columns={"stream", "version"})})
 */
class Events
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $stream;

    /**
     * @var integer
     */
    protected $version;

    /**
     * @ORM\Column(name="saved_at")
     * @var \DateTime
     */
    protected $savedAt;

    /**
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $data;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $metadata;

}