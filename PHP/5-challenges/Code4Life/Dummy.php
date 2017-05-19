<?php
/**
 * Bring data on patient samples from the diagnosis machine to the laboratory with enough molecules to produce medicine!
 **/

define('DEBUG', true);

/**
 * @param string $trace
 * @param bool $force
 */
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
     * @return int
     */
    public function getTotalCost();

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
     * @return int
     */
    public function getTotalCost()
    {
        $totalCost = 0;
        foreach ($this->costs as $cost) {
            $totalCost += $cost;
        }
        return $totalCost;
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
    const RANK_MIN = 1;
    const RANK_MIDDLE = 2;
    const RANK_MAX = 3;
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
        return $this;
    }

    /**
     * @return string
     */
    public function act()
    {
        if ($this->eta !== 0) {
            return "ON THE ROAD AGAIN\n";
        }

        $carriedSampleData = $this->getSelfCarriedSampleData();
        $chosenSampleData = $this->chooseSampleData();
        $sampleDataToFill = $this->chooseSampleDataToFill();
        $filledSampleData = $this->getFilledSampleData();

        //SAMPLES module
        if (count($carriedSampleData) === 0 && count($chosenSampleData) === 0) {
            if (TARGET_SAMPLES !== $this->target) {
                return "GOTO " . TARGET_SAMPLES . "\n";
            } else {
                return "CONNECT " . $this->chooseRank() . "\n";
            }
        } elseif (count($chosenSampleData) < 3
            && count($carriedSampleData) < 3
            && TARGET_SAMPLES === $this->target
        ) {
            return "CONNECT " . $this->chooseRank() . "\n";
        }

        //DIAGNOSIS module
        $selfCarriedUndiagnosedSampleData = $this->getSelfCarriedUndiagnosedSampleData();
        if (count($carriedSampleData) === 0) { //If I don't carry anything.
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO " . TARGET_DIAGNOSIS . "\n";
            } elseif (count($chosenSampleData) !== 0) {
                return "CONNECT " . reset($chosenSampleData)->getId() . "\n";
            }
        } elseif (count($selfCarriedUndiagnosedSampleData) !== 0) { //If I carry some undiagnosed sample data.
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO " . TARGET_DIAGNOSIS . "\n";
            } else {
                return "CONNECT " . $this->chooseCarriedSampleDataToDiagnose()->getId() . "\n";
            }
        } elseif (count($carriedSampleData) < 3
            && TARGET_DIAGNOSIS === $this->target
            && count($chosenSampleData) !== 0
        ) { //If I carry less than 3 sample data, I'm to DIAGNOSIS module and I found a fillable sample data.
            return "CONNECT " . reset($chosenSampleData)->getId() . "\n";
        }

        if (count($sampleDataToFill) === 0 && count($filledSampleData) === 0) { //If I carry some unfilled sample data and can't fill anyone.
            _('No fillable sample data');
            _($carriedSampleData);
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO " . TARGET_DIAGNOSIS . "\n";
            } else {
                return "CONNECT " . $this->chooseSampleDataToFree()->getId() . "\n";
            }
        }

        //MOLECULES module
        if (count($sampleDataToFill) !== 0) {
            foreach ($sampleDataToFill as $sampleData) {
                $costs = $sampleData->getCosts();
                foreach ($costs as $molecule => $cost) {
                    if ($cost > ($this->storages[$molecule] + $this->expertises[$molecule])) {
                        if (TARGET_MOLECULES !== $this->target) {
                            return "GOTO " . TARGET_MOLECULES . "\n";
                        } elseif ($this->moleculeFillable($molecule, $cost)) {
                            return "CONNECT " . $molecule . "\n";
                        }
                    }
                }
            }
        }

        //LABORATORY module
        if (count($filledSampleData) !== 0) {
            if (TARGET_LABORATORY !== $this->target) {
                return "GOTO " . TARGET_LABORATORY . "\n";
            } else {
                return "CONNECT " . reset($filledSampleData)->getId() . "\n";
            }
        }

        //Default action
        return "WAIT\n";
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function getSelfCarriedSampleData()
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
     * @return SampleDataInterface[]
     */
    protected function chooseSampleData()
    {
        $maxSampleDataHealth = 0;
        $possibleSampleData = array();
        foreach ($this->sampleData as $sampleData) {
            if ($sampleData->isFree() && $this->isFillable($sampleData)) {
                if ($sampleData->getHealth() > $maxSampleDataHealth) {
                    $maxSampleDataHealth = $sampleData->getHealth();
                    $possibleSampleData = array($sampleData->getId() => $sampleData);
                } elseif ($sampleData->getHealth() === $maxSampleDataHealth) {
                    $possibleSampleData[$sampleData->getId()] = $sampleData;
                }
            }
        }

        return $possibleSampleData;
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
     * @return SampleDataInterface[]
     */
    protected function getFilledSampleData()
    {
        $carriedSampleData = $this->getSelfCarriedSampleData();
        $filledSampleData = array();
        foreach ($carriedSampleData as $sampleData) {
            $costs = $sampleData->getCosts();
            $filled = true;
            foreach ($costs as $molecule => $cost) {
                if ($cost > ($this->storages[$molecule] + $this->expertises[$molecule])) {
                    $filled = false;
                    break;
                }
            }
            if ($filled) {
                $filledSampleData[$sampleData->getId()] = $sampleData;
            }
        }

        return $filledSampleData;
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
        $amountOfExpertises = $this->getAmountOfExpertises();
        $carriedSampleData = $this->getSelfCarriedSampleData();

        if ($amountOfExpertises === 0) {
            if (count($carriedSampleData) === 0) {
                return self::RANK_MIN;
            } else {
                return self::RANK_MIDDLE;
            }
        }

        if ($amountOfExpertises < 3 || $this->isStorageFull()) {
            return self::RANK_MIDDLE;
        }


        if ($amountOfExpertises < 10) {
            if (count($carriedSampleData) < 2) {
                return self::RANK_MIDDLE;
            }
            return self::RANK_MAX;
        }

        return self::RANK_MAX;
    }

    /**
     * @return SampleDataInterface[]
     */
    protected function chooseSampleDataToFill()
    {
        //If I'm full, don't try to get more molecules.
        if ($this->isStorageFull()) {
            return array();
        }

        $temporaryFreeSlotStorage = $this->getFreeSlotsStorage();
        $sampleDataToFill = array();
        foreach ($this->getSelfCarriedSampleData() as $sampleData) {
            if ($this->isFilled($sampleData)) {
                //If I already have all molecules for this sample data, ignore it.
                continue;
            }

            $fillable = true;
            $totalRemainingCost = 0;
            foreach ($sampleData->getCosts() as $molecule => $cost) {
                if (!$this->moleculeFillable($molecule, $cost)) {
                    _('Sample data no fillable:');
                    _($sampleData);
                    $fillable = false;
                    break;
                }
                $totalRemainingCost += $cost - $this->storages[$molecule] - $this->expertises[$molecule];
            }
            if ($fillable && $totalRemainingCost <= $temporaryFreeSlotStorage) {
                $sampleDataToFill[$sampleData->getId()] = $sampleData;
                $temporaryFreeSlotStorage -= $totalRemainingCost;
            }
        }
        return $sampleDataToFill;
    }

    /**
     * @return int
     */
    protected function getFreeSlotsStorage()
    {
        $storageAmount = 0;
        foreach ($this->storages as $amount) {
            $storageAmount += $amount;
        }
        return 10 - $storageAmount;

    }

    /**
     * @return SampleDataInterface
     */
    protected function chooseSampleDataToFree()
    {
        $selfCarriedSampleData = $this->getSelfCarriedSampleData();
        return reset($selfCarriedSampleData);
    }

    /**
     * @param SampleDataInterface $sampleData
     *
     * @return bool
     */
    protected function isFilled(SampleDataInterface $sampleData)
    {
        foreach ($sampleData->getCosts() as $molecule => $cost) {
            if ($cost > ($this->storages[$molecule] + $this->expertises[$molecule])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param SampleDataInterface $sampleData
     *
     * @return bool
     */
    protected function isFillable(SampleDataInterface $sampleData)
    {
        _(__METHOD__);
        _($sampleData);
        $temporaryFreeSlots = $this->getFreeSlotsStorage();
        foreach ($sampleData->getCosts() as $molecule => $cost) {
            if (!$this->moleculeFillable($molecule, $cost)) {
                return false;
            }
            $temporaryFreeSlots -= max(0, $cost - $this->expertises[$molecule] - $this->storages[$molecule]);
        }
        _('Temporary free slots: ' . $temporaryFreeSlots);
        return $temporaryFreeSlots >= 0;
    }

    /**
     * @return int
     */
    protected function getAmountOfExpertises()
    {
        return array_sum($this->expertises);
    }

    /**
     * @param string $molecule
     * @param int $needed
     *
     * @return bool
     */
    protected function moleculeFillable($molecule, $needed)
    {
        _(__METHOD__);
        _($molecule);
        _($needed);
        _($this->availabilities[$molecule]);
        _($this->storages[$molecule]);
        _($this->expertises[$molecule]);
        _($needed <= ($this->availabilities[$molecule] + $this->storages[$molecule] + $this->expertises[$molecule]));
        return $needed <= ($this->availabilities[$molecule] + $this->storages[$molecule] + $this->expertises[$molecule]);
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
