<?php

declare(strict_types=1);

namespace Alexispe\SyliusRoundUpPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait RoundableTrait
{
    /** @ORM\Column(name="rounded", type="boolean", nullable=false, options={"default"=false}) */
    protected bool $rounded = false;

    public function isRounded(): bool
    {
        return $this->rounded;
    }

    public function setRounded(bool $rounded): void
    {
        $this->rounded = $rounded;
    }
}
