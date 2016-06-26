<?php
/**
 * Send your busters out into the fog to trap ghosts and bring them home!
 **/

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

interface ManagerInterface
{
    public function __construct($bustersPerPlayer, $ghostCount, $myTeamId, $map);

    public function newTurn();

    public function getMyBustersId();

    public function getEntity($entityType, $entityId);

    public function updateEntity($entityType, $entityId, EntityInterface $entity);

    public function analyse();

    public function getBusterAction($busterId);
}

class DummyManager implements ManagerInterface
{
    const ENTITYTYPE_TEAM0 = 0;
    const ENTITYTYPE_TEAM1 = 1;
    const ENTITYTYPE_GHOST = -1;

    protected $bustersPerPlayer;
    protected $ghostCount;
    protected $myTeamId;
    /** @var MapInterface */
    protected $map;

    /** @var BusterInterface[] */
    protected $myBusters;
    /** @var GhostInterface[] */
    protected $ghosts;

    /** @var string[] */
    protected $actions;

    public function __construct($bustersPerPlayer, $ghostCount, $myTeamId, $map)
    {
        $this->bustersPerPlayer = $bustersPerPlayer;
        $this->ghostCount = $ghostCount;
        $this->myTeamId = $myTeamId;
        $this->map = $map;

        $this->myBusters = [];
    }

    public function newTurn()
    {
        $this->ghosts = [];
        $this->actions = [];
    }

    public function getMyBustersId()
    {
        return array_keys($this->myBusters);
    }

    public function getEntity($entityType, $entityId)
    {
        //Look for a ghost?
        if ($entityType === static::ENTITYTYPE_GHOST) {
            return $this->getGhost($entityId);
        }

        //Look for ennemy?
        if ($entityType !== $this->myTeamId) {
            return false;
        }

        //Look for ally?
        if (!array_key_exists($entityId, $this->myBusters)) {
            return false;
        }

        return $this->myBusters[$entityId];
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost or ennemy's buster
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        if ($this->myTeamId !== $entityType) {
            return;
        }

        $this->myBusters[$entityId] = $entity;
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $spottedGhosts = [];

        foreach ($this->myBusters as $busterId => $entity) {
            $busterCoordinates = $entity->getCoordinates();

            //Go home and release ghost.
            if ($entity->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $entity->release();
                    continue;
                }

                $this->actions[$busterId] = 'MOVE ' . implode(' ', $baseCoordinates);
                continue;
            }

            //Is there a closed enough ghost?
            foreach ($this->ghosts as $ghostId => $ghost) {
                if (array_key_exists($ghostId, $spottedGhosts)) {
                    continue;
                }

                $ghostCoordinates = $ghost->getCoordinates();
                $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]);

                //Too close!
                if ($distance < 900) {
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $busterCoordinates);
                    continue 2;
                }

                //Right distance!
                if ($distance < 1760) {
                    $this->actions[$busterId] = 'BUST ' . $ghostId;
                    $entity->bust();
                    $spottedGhosts[$ghostId] = true;
                    continue 2;
                }

                //Still too far!
                if ($distance < 2200) {
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $ghostCoordinates);
                    $spottedGhosts[$ghostId] = true;
                    continue 2;
                }
            }

            //No? ... So let's move...
            $this->actions[$busterId] = 'MOVE ' . mt_rand(0, 16000) . ' ' . mt_rand(0, 9000);
        }
    }

    public function getBusterAction($busterId)
    {
        if (array_key_exists($busterId, $this->actions)) {
            return $this->actions[$busterId];
        }

        return 'MOVE 8000 4500';
    }
}

class Rule
{
    public static function getDistance($x1, $y1, $x2, $y2)
    {
        return sqrt(pow(abs($x1 - $x2), 2) + pow(abs($y1 - $y2), 2));
    }
}

interface MapInterface
{
    public function getBaseCoordinates();
}

class Map implements MapInterface
{
    protected $base;

    public function __construct($team)
    {
        $this->base = $this->defineBaseCoordinates($team);
    }

    protected function defineBaseCoordinates($team)
    {
        if (0 === $team) {
            return [0, 0];
        }
        return [16000, 9000];
    }

    public function getBaseCoordinates() {
        return $this->base;
    }
}

class EntityFactory
{
    public static function factory($entityType, $entityId, $x, $y, $state, $value)
    {

    }
}

interface EntityInterface
{
    public function __construct($id, $type);

    public function newTurn($x, $y, $state, $value);

    public function getCoordinates();
}

interface GhostInterface extends EntityInterface
{

}

class Ghost implements GhostInterface
{
    protected $id;
    protected $type;
    protected $coordinates;

    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function newTurn($x, $y, $state, $value)
    {
        $this->coordinates = [$x, $y];
    }

    public function getCoordinates()
    {
        return $this->coordinates;
    }
}

interface BusterInterface extends EntityInterface
{
    public function hasBusted();
    public function bust();
    public function release();
}

class DummyBuster implements BusterInterface
{
    protected $id;
    protected $type;
    protected $coordinates;
    protected $busted = false;

    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function newTurn($x, $y, $state, $value)
    {
        $this->coordinates = [$x, $y];
    }

    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @return boolean
     */
    public function hasBusted()
    {
        return $this->busted;
    }

    public function bust()
    {
        $this->busted = true;
    }

    public function release()
    {
        $this->busted = false;
    }
}

    // the amount of busters you control
fscanf(STDIN, "%d", $bustersPerPlayer);
// the amount of ghosts on the map
fscanf(STDIN, "%d", $ghostCount);
// if this is 0, your base is on the top left of the map, if it is one, on the bottom right
fscanf(STDIN, "%d", $myTeamId);
_('My team id: ' . $myTeamId);

$map = new Map($myTeamId);
$manager = new DummyManager($bustersPerPlayer, $ghostCount, $myTeamId, $map);

// game loop
while (TRUE) {
    $manager->newTurn();
    // the number of busters and ghosts visible to you
    fscanf(STDIN, "%d", $entities);
    for ($i = 0; $i < $entities; $i++)
    {
        fscanf(STDIN, "%d %d %d %d %d %d",
            $entityId, // buster id or ghost id
            $x,
            $y, // position of this buster / ghost
            $entityType, // the team id if it is a buster, -1 if it is a ghost.
            $state, // For busters: 0=idle, 1=carrying a ghost.
            $value // For busters: Ghost id being carried. For ghosts: number of busters attempting to trap this ghost.
        );

        if ($entityType === DummyManager::ENTITYTYPE_GHOST) {
            $ghost = new Ghost($entityId, $entityType);
            $ghost->newTurn($x, $y, $state, $value);
            $manager->updateEntity($entityType, $entityId, $ghost);
            continue;
        }

        if ($entityType !== $myTeamId) {
            _('Ennemy');
            continue;
        }

        /** @var EntityInterface $entity */
        $entity = $manager->getEntity($entityType, $entityId);

        if (false === $entity) {
            $entity = new DummyBuster($entityId, $entityType);
        }

        $entity->newTurn($x, $y, $state, $value);
        $manager->updateEntity($entityType, $entityId, $entity);
    }

    $manager->analyse();

    foreach ($manager->getMyBustersId() as $entityId)
    {
        // MOVE x y | BUST id | RELEASE
        echo $manager->getBusterAction($entityId) . "\n";
    }
}
