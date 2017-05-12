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

define('TARGET_DIAGNOSIS', 'DIAGNOSIS');
define('TARGET_MOLECULES', 'MOLECULES');
define('TARGET_LABORATORY', 'LABORATORY');

/**
 * Class SampleData
 */
class SampleData {
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
     * @param SampleData $sampleData
     *
     * @return DummyRobot
     */
    public function setSampleData(SampleData $sampleData);

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
     * @var SampleData[]
     */
    protected $sampleData = array();

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
     * @param SampleData $sampleData
     *
     * @return DummyRobot
     */
    public function setSampleData(SampleData $sampleData)
    {
        $this->sampleData[$sampleData->getId()] = $sampleData;

        return $this;
    }

    /**
     * @return string
     */
    public function act()
    {
        $carriedSampleData = $this->getCarriedSampleData();
        if (count($carriedSampleData) < 1) {
            if (TARGET_DIAGNOSIS !== $this->target) {
                return "GOTO DIAGNOSIS\n";
            } else {
                return "CONNECT " . $this->chooseSampleData()->getId() . "\n";
            }
        } elseif (count($carriedSampleData) < 3 && TARGET_DIAGNOSIS === $this->target) {
            return "CONNECT " . $this->chooseSampleData()->getId() . "\n";
        }

        try {
            $filledSampleData = $this->getFilledSampleData();
        } catch (Exception $e) {
            foreach ($carriedSampleData as $sampleData) {
                $costs = $sampleData->getCosts();
                foreach ($costs as $molecule => $cost) {
                    if ($cost > $this->storages[$molecule]) {
                        if (TARGET_MOLECULES !== $this->target) {
                            return "GOTO MOLECULES\n";
                        } else {
                            return "CONNECT " . $molecule . "\n";
                        }
                    }
                }
            }
        }

        if (TARGET_LABORATORY !== $this->target) {
            return "GOTO LABORATORY\n";
        } else {
            return "CONNECT " . $filledSampleData->getId() . "\n";
        }
    }

    /**
     * @return SampleData[]
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
     * @return SampleData
     * @throws Exception
     */
    protected function chooseSampleData()
    {
        foreach ($this->sampleData as $sampleData) {
            if (-1 === $sampleData->getCarriedBy()) {
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
     * @return SampleData
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
    fscanf(STDIN, "%d %d %d %d %d",
        $availableA,
        $availableB,
        $availableC,
        $availableD,
        $availableE
    );
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
