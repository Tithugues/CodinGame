<?php
/**
 * Bring data on patient samples from the diagnosis machine to the laboratory with enough molecules to produce medicine!
 **/

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

define('TARGET_SAMPLES', 'SAMPLES');
define('TARGET_DIAGNOSIS', 'DIAGNOSIS');
define('TARGET_MOLECULES', 'MOLECULES');
define('TARGET_LABORATORY', 'LABORATORY');

/**
 * Interface SampleDataInterface
 */
interface SampleDataInterface {
    /**
     * SampleData constructor.
     *
     * @param int $id
     * @param int $carriedBy
     * @param int $rank
     * @param int $expertiseGain
     * @param int $health
     * @param int[] $costs
     */
    public function __construct($id, $carriedBy, $rank, $expertiseGain, $health, $costs);

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getCarriedBy();

    /**
     * @return int
     */
    public function getHealth();

    /**
     * @return int[]
     */
    public function getCosts();

    /**
     * @return bool
     */
    public function isFree();

    /**
     * @return bool
     */
    public function isDiagnosed();
}

/**
 * Class SampleData
 */
class SampleData implements SampleDataInterface {
    /** @var int */
    protected $id;

    /** @var int */
    protected $carriedBy;

    /** @var int */
    protected $rank;

    /** @var int */
    protected $expertiseGain;

    /** @var int */
    protected $health;

    /** @var int[] */
    protected $costs;

    /**
     * SampleData constructor.
     *
     * @param int $id
     * @param int $carriedBy
     * @param int $rank
     * @param int $expertiseGain
     * @param int $health
     * @param int[] $costs
     */
    public function __construct($id, $carriedBy, $rank, $expertiseGain, $health, $costs)
    {
        $this->id = $id;
        $this->carriedBy = $carriedBy;
        $this->rank = $rank;
        $this->expertiseGain = $expertiseGain;
        $this->health = $health;
        $this->costs = $costs;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCarriedBy()
    {
        return $this->carriedBy;
    }

    /**
     * @return int
     */
    public function getHealth()
    {
        return $this->health;
    }

    /**
     * @return int[]
     */
    public function getCosts()
    {
        return $this->costs;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return -1 === $this->carriedBy;
    }

    /**
     * @return bool
     */
    public function isDiagnosed()
    {
        return reset($this->costs) !== -1;
    }
}

/**
 * Interface RobotInterface
 */
interface RobotInterface {
    /**
     * DummyRobot constructor.
     *
     * @param string $target
     * @param int $eta
     * @param int $score
     * @param int[] $storages
     * @param int[] $expertises
     */
    public function __construct($target, $eta, $score, $storages, $expertises);

    /**
     * @param SampleDataInterface $sampleData
     *
     * @return $this
     */
    public function setSampleData(SampleDataInterface $sampleData);

    /**
     * @param int[] $availabilities
     *
     * @return $this
     */
    public function setAvailabilities($availabilities);

    /**
     * @return string
     */
    public function act();
}

/**
 * Class DummyRobot
 */
class DummyRobot implements RobotInterface {
    /**
     * @var string
     */
    private $target;
    /**
     * @var int
     */
    private $eta;
    /**
     * @var int
     */
    private $score;
    /**
     * @var int[]
     */
    private $storages;
    /**
     * @var int[]
     */
    private $expertises;
    /**
     * @var SampleDataInterface[]
     */
    protected $sampleData = array();
    /**
     * @var int[]
     */
    private $availabilities;

    /**
     * DummyRobot constructor.
     *
     * @param string $target
     * @param int $eta
     * @param int $score
     * @param int[] $storages
     * @param int[] $expertises
     */
    public function __construct($target, $eta, $score, $storages, $expertises)
    {
        $this->target = $target;
        $this->eta = $eta;
        $this->score = $score;
        $this->storages = $storages;
        $this->expertises = $expertises;
    }

    /**
     * @param SampleDataInterface $sampleData
     *
     * @return DummyRobot
     */
    public function setSampleData(SampleDataInterface $sampleData)
    {
        $this->sampleData[$sampleData->getId()] = $sampleData;

        return $this;
    }

    /**
     * @param int[] $availabilities
     *
     * @return $this
     */
    public function setAvailabilities($availabilities)
    {
        $this->availabilities = $availabilities;
    }

    /**
     * @return string
     */
    public function act()
    {
        _($this->sampleData);

        //SAMPLES module
        $carriedSampleData = $this->getCarriedSampleData();
        $freeSampleData = $this->getFreeSampleData();
        if (count($carriedSampleData) < 1 && count($freeSampleData) < 1) {
            if (TARGET_SAMPLES !== $this->target) {
                return "GOTO " . TARGET_SAMPLES . "\n";
            } else {
                return "CONNECT " . $this->chooseRank() . "\n";
            }
        } elseif (count($freeSampleData) < 3
            && count($this->getCarriedSampleData()) < 3
            && TARGET_SAMPLES === $this->target
        ) {
            return "CONNECT " . $this->chooseRank() . "\n";
        }

        //DIAGNOSIS module
        $selfCarriedUndiagnosedSampleData = $this->getSelfCarriedUndiagnosedSampleData();
        if (count($carriedSampleData) < 1) {
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO " . TARGET_DIAGNOSIS . "\n";
            } else {
                return "CONNECT " . $this->chooseSampleData()->getId() . "\n";
            }
        } elseif (count($selfCarriedUndiagnosedSampleData) > 0) {
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO " . TARGET_DIAGNOSIS . "\n";
            } else {
                return "CONNECT " . $this->chooseCarriedSampleDataToDiagnose()->getId() . "\n";
            }
        } elseif (count($carriedSampleData) < 3 && TARGET_DIAGNOSIS === $this->target) {
            return "CONNECT " . $this->chooseSampleData()->getId() . "\n";
        }

        //MOLECULES module
        try {
            $filledSampleData = $this->getFilledSampleData();
        } catch (Exception $e) {
            foreach ($carriedSampleData as $sampleData) {
                $costs = $sampleData->getCosts();
                foreach ($costs as $molecule => $cost) {
                    if ($cost > $this->storages[$molecule]) {
                        if (TARGET_MOLECULES !== $this->target) {
                            return "GOTO " . TARGET_MOLECULES . "\n";
                        } elseif ($this->enoughAvailabilities($molecule, $cost - $this->storages[$molecule])) {
                            return "CONNECT " . $molecule . "\n";
                        }
                    }
                }
            }
            return "WAIT\n";
        }

        //LABORATORY module
        if (TARGET_LABORATORY !== $this->target) {
            return "GOTO " . TARGET_LABORATORY . "\n";
        } else {
            return "CONNECT " . $filledSampleData->getId() . "\n";
        }
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function getCarriedSampleData()
    {
        $carriedSampleData = array();
        foreach ($this->sampleData as $sampleData) {
            if (0 === $sampleData->getCarriedBy()) {
                $carriedSampleData[$sampleData->getId()] = $sampleData;
            }
        }
        return $carriedSampleData;
    }

    /**
     * @return SampleDataInterface
     * @throws Exception
     */
    protected function chooseSampleData()
    {
        $maxSampleDataHealth = 0;

        foreach ($this->sampleData as $sampleData) {
            if ($sampleData->isFree() && $sampleData->getHealth() > $maxSampleDataHealth) {
                $maxSampleDataHealth = $sampleData->getHealth();
            }
        }

        foreach ($this->sampleData as $sampleData) {
            if ($sampleData->isFree() && $sampleData->getHealth() === $maxSampleDataHealth) {
                return $sampleData;
            }
        }

        throw new Exception('No remaining sample data');
    }

    /**
     * @return bool
     */
    protected function isStorageFull()
    {
        $storageAmount = 0;
        foreach ($this->storages as $amount) {
            $storageAmount += $amount;
        }
        return 10 === $storageAmount;
    }

    /**
     * @return SampleDataInterface
     * @throws Exception
     */
    protected function getFilledSampleData()
    {
        $carriedSampleData = $this->getCarriedSampleData();
        foreach ($carriedSampleData as $sampleData) {
            $costs = $sampleData->getCosts();
            $filled = true;
            foreach ($costs as $molecule => $cost) {
                if ($cost > $this->storages[$molecule]) {
                    $filled = false;
                }
            }
            if ($filled) {
                return $sampleData;
            }
        }

        throw new Exception('No filled sample data');
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function getFreeSampleData()
    {
        $freeSampleData = array();
        foreach ($this->sampleData as $sampleData) {
            if ($sampleData->isFree()) {
                $freeSampleData[$sampleData->getId()] = $sampleData;
            }
        }
        return $freeSampleData;
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function getFreeDiagnosedSampleData()
    {
        throw new Exception(__METHOD__);
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function getSelfCarriedUndiagnosedSampleData()
    {
        $carriedUndiagnosedSampleData = array();
        foreach ($this->sampleData as $sampleData) {
            if (0 === $sampleData->getCarriedBy() && !$sampleData->isDiagnosed()) {
                $carriedUndiagnosedSampleData[$sampleData->getId()] = $sampleData;
            }
        }
        return $carriedUndiagnosedSampleData;
    }

    /**
     * @return SampleDataInterface
     * @throws Exception
     */
    protected function chooseCarriedSampleDataToDiagnose()
    {
        $selfCarriedUndiagnosedSampleData = $this->getSelfCarriedUndiagnosedSampleData();
        return reset($selfCarriedUndiagnosedSampleData);
    }

    /**
     * @return int
     */
    protected function chooseRank()
    {
        return 2;
    }

    /**
     * @param string $molecule
     * @param int $needed
     *
     * @return bool
     */
    protected function enoughAvailabilities($molecule, $needed)
    {
        return $this->availabilities[$molecule] >= $needed;
    }
}

fscanf(STDIN, "%d",
    $projectCount
);
for ($i = 0; $i < $projectCount; $i++)
{
    fscanf(STDIN, "%d %d %d %d %d",
        $a,
        $b,
        $c,
        $d,
        $e
    );
}

// game loop
while (TRUE)
{
    for ($i = 0; $i < 2; $i++)
    {
        $storages = array();
        $expertises = array();
        fscanf(STDIN, "%s %d %d %d %d %d %d %d %d %d %d %d %d",
            $target,
            $eta,
            $score,
            $storages['A'],
            $storages['B'],
            $storages['C'],
            $storages['D'],
            $storages['E'],
            $expertises['A'],
            $expertises['B'],
            $expertises['C'],
            $expertises['D'],
            $expertises['E']
        );

        if (0 === $i) {
            /** @var RobotInterface $robot */
            $robot = new DummyRobot($target, $eta, $score, $storages, $expertises);
        }
    }
    $availabilities = array();
    fscanf(STDIN, "%d %d %d %d %d",
        $availabilities['A'],
        $availabilities['B'],
        $availabilities['C'],
        $availabilities['D'],
        $availabilities['E']
    );
    $robot->setAvailabilities($availabilities);
    fscanf(STDIN, "%d",
        $sampleCount
    );
    for ($i = 0; $i < $sampleCount; $i++)
    {
        $costs = array();
        fscanf(STDIN, "%d %d %d %s %d %d %d %d %d %d",
            $sampleId,
            $carriedBy,
            $rank,
            $expertiseGain,
            $health,
            $costs['A'],
            $costs['B'],
            $costs['C'],
            $costs['D'],
            $costs['E']
        );

        $robot->setSampleData(new SampleData($sampleId, $carriedBy, $rank, $expertiseGain, $health, $costs));
    }

    echo $robot->act();
}
