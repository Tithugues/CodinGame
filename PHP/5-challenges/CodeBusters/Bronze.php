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

        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
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

class DummyWithEnnemyManager implements ManagerInterface
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
    /** @var BusterInterface[] */
    protected $hisBusters;
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
        $this->hisBusters = [];
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
            return $this->getEnnemy($entityId);
        }

        //Look for ally?
        return $this->getAlly($entityId);
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    protected function getEnnemy($entityId)
    {
        return array_key_exists($entityId, $this->hisBusters) ? $this->hisBusters[$entityId] : false;
    }

    protected function getAlly($entityId)
    {
        return array_key_exists($entityId, $this->myBusters) ? $this->myBusters[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost or ennemy's buster
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        if ($this->myTeamId !== $entityType) {
            $this->hisBusters[$entityId] = $entity;
            return;
        }

        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $spottedGhosts = [];
        $stunnedEnnemy = [];

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();

            //Ghost in bag? Go home and release ghost.
            if ($myBuster->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $myBuster->release();
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
                    $myBuster->bust();
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

            if (!$myBuster->hasStunned()) {
                //Is there a closed enough ennemy to stun him?
                foreach ($this->hisBusters as $ennemyId => $ennemy) {
                    if ($ennemy->isStunned()) {
                        continue;
                    }

                    $ennemyCoordinates = $ennemy->getCoordinates();
                    $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ennemyCoordinates[0], $ennemyCoordinates[1]);

                    //Right distance!
                    if ($distance < 1760) {
                        $this->actions[$busterId] = 'STUN ' . $ennemyId;
                        $myBuster->stun();
                        $ennemy->stunned();
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }
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

class ExplorerManager implements ManagerInterface
{
    const ENTITYTYPE_TEAM0 = 0;
    const ENTITYTYPE_TEAM1 = 1;
    const ENTITYTYPE_GHOST = -1;

    protected $bustersPerPlayer;
    protected $ghostCount;
    protected $myTeamId;
    /** @var MapInterface */
    protected $map;

    protected $areas;

    /** @var BusterInterface[] */
    protected $myBusters;
    /** @var BusterInterface[] */
    protected $hisBusters;
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

        $this->defineAreas();
    }

    private function defineAreas()
    {
        if ($this->bustersPerPlayer == 2) {
            $this->areas = [
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 3) {
            $this->areas = [
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 4) {
            $this->areas = [
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
            ];
        }
    }

    public function newTurn()
    {
        $this->ghosts = [];
        $this->hisBusters = [];
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
            return $this->getEnnemy($entityId);
        }

        //Look for ally?
        return $this->getAlly($entityId);
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    protected function getEnnemy($entityId)
    {
        return array_key_exists($entityId, $this->hisBusters) ? $this->hisBusters[$entityId] : false;
    }

    protected function getAlly($entityId)
    {
        return array_key_exists($entityId, $this->myBusters) ? $this->myBusters[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost or ennemy's buster
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        if ($this->myTeamId !== $entityType) {
            $this->hisBusters[$entityId] = $entity;
            return;
        }

        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
    }

    protected function fixCoordinates($coordinates)
    {
        $coordinates[0] = max(0, min(16000, $coordinates[0]));
        $coordinates[1] = max(0, min(9000, $coordinates[1]));
        return $coordinates;
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $spottedGhosts = [];
        $stunnedEnnemy = [];

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();

            //Ghost in bag? Go home and release ghost.
            if ($myBuster->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $myBuster->release();
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
                    $nextBusterCoordinates = $busterCoordinates;
                    $nextBusterCoordinates[0] += mt_rand(-600, 600);
                    $nextBusterCoordinates[1] += mt_rand(-600, 600);
                    $nextBusterCoordinates = $this->fixCoordinates($nextBusterCoordinates);
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $nextBusterCoordinates);
                    unset($nextBusterCoordinates);
                    continue 2;
                }

                //Right distance!
                if ($distance < 1760) {
                    $this->actions[$busterId] = 'BUST ' . $ghostId;
                    $myBuster->bust();
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

            if (!$myBuster->hasStunned()) {
                //Is there a closed enough ennemy to stun him?
                foreach ($this->hisBusters as $ennemyId => $ennemy) {
                    if ($ennemy->isStunned() || array_key_exists($ennemyId, $stunnedEnnemy)) {
                        continue;
                    }

                    $ennemyCoordinates = $ennemy->getCoordinates();
                    $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ennemyCoordinates[0], $ennemyCoordinates[1]);

                    //Right distance!
                    if ($distance < 1760) {
                        $this->actions[$busterId] = 'STUN ' . $ennemyId;
                        $myBuster->stun();
                        $ennemy->stunned();
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }

                    //Still too far!
                    if ($distance < 2200) {
                        $this->actions[$busterId] = 'MOVE ' . implode(' ', $ennemyCoordinates);
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }
                }
            }

            //No? ... So let's move...
            if (!array_key_exists($busterId, $this->myBusters)) {
                $this->actions[$busterId] = 'MOVE ' . mt_rand(0, 16000) . ' ' . mt_rand(0, 9000);
            } else {
                $this->actions[$busterId] = 'MOVE ' . mt_rand($this->areas[$busterId][0][0], $this->areas[$busterId][0][1]) . ' ' . mt_rand($this->areas[$busterId][1][0], $this->areas[$busterId][1][1]);
            }
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

class ExploreRandomAreasManager implements ManagerInterface
{
    const ENTITYTYPE_TEAM0 = 0;
    const ENTITYTYPE_TEAM1 = 1;
    const ENTITYTYPE_GHOST = -1;

    protected $bustersPerPlayer;
    protected $ghostCount;
    protected $myTeamId;
    /** @var MapInterface */
    protected $map;

    protected $areas;

    /** @var BusterInterface[] */
    protected $myBusters;
    /** @var BusterInterface[] */
    protected $hisBusters;
    /** @var GhostInterface[] */
    protected $ghosts;

    /** @var string[] */
    protected $actions;

    protected $lastAreaChange;

    public function __construct($bustersPerPlayer, $ghostCount, $myTeamId, $map)
    {
        $this->bustersPerPlayer = $bustersPerPlayer;
        $this->ghostCount = $ghostCount;
        $this->myTeamId = $myTeamId;
        $this->map = $map;
        $this->lastAreaChange = 0;

        $this->myBusters = [];

        $this->defineAreas();
    }

    private function defineAreas()
    {
        if (0 === $this->myTeamId) {
            $busterId = 0;
        } else {
            $busterId = $this->bustersPerPlayer;
        }

        if ($this->bustersPerPlayer == 2) {
            $this->areas = [
                $busterId++ => [[0, 7500], [0, 9000]],
                $busterId++ => [[8501, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 3) {
            $this->areas = [
                $busterId++ => [[0, 4500], [0, 9000]],
                $busterId++ => [[5001, 10000], [0, 9000]],
                $busterId++ => [[10501, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 4) {
            $this->areas = [
                $busterId++ => [[0, 7500], [0, 4000]],
                $busterId++ => [[0, 7500], [5000, 9000]],
                $busterId++ => [[8501, 16000], [0, 4000]],
                $busterId++ => [[8701, 16000], [5000, 9000]],
            ];
        }
    }

    private function redefineAreas()
    {
        $keys = array_keys($this->areas);
        shuffle($this->areas);
        $this->areas = array_combine($keys, $this->areas);
    }

    public function newTurn()
    {
        $this->ghosts = [];
        $this->hisBusters = [];
        $this->actions = [];
        if (++$this->lastAreaChange >= 15) {
            $this->lastAreaChange = 0;
            $this->redefineAreas();
        }
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
            return $this->getEnnemy($entityId);
        }

        //Look for ally?
        return $this->getAlly($entityId);
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    protected function getEnnemy($entityId)
    {
        return array_key_exists($entityId, $this->hisBusters) ? $this->hisBusters[$entityId] : false;
    }

    protected function getAlly($entityId)
    {
        return array_key_exists($entityId, $this->myBusters) ? $this->myBusters[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost or ennemy's buster
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        if ($this->myTeamId !== $entityType) {
            $this->hisBusters[$entityId] = $entity;
            return;
        }

        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
    }

    protected function fixCoordinates($coordinates)
    {
        $coordinates[0] = max(0, min(16000, $coordinates[0]));
        $coordinates[1] = max(0, min(9000, $coordinates[1]));
        return $coordinates;
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $spottedGhosts = [];
        $stunnedEnnemy = [];

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();

            //Ghost in bag? Go home and release ghost.
            if ($myBuster->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $myBuster->release();
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
                    $nextBusterCoordinates = $busterCoordinates;
                    $nextBusterCoordinates[0] += mt_rand(-600, 600);
                    $nextBusterCoordinates[1] += mt_rand(-600, 600);
                    $nextBusterCoordinates = $this->fixCoordinates($nextBusterCoordinates);
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $nextBusterCoordinates);
                    unset($nextBusterCoordinates);
                    continue 2;
                }

                //Right distance!
                if ($distance < 1760) {
                    $this->actions[$busterId] = 'BUST ' . $ghostId;
                    $myBuster->bust();
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

            if (!$myBuster->hasStunned()) {
                //Is there a closed enough ennemy to stun him?
                foreach ($this->hisBusters as $ennemyId => $ennemy) {
                    if ($ennemy->isStunned() || array_key_exists($ennemyId, $stunnedEnnemy)) {
                        continue;
                    }

                    $ennemyCoordinates = $ennemy->getCoordinates();
                    $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ennemyCoordinates[0], $ennemyCoordinates[1]);

                    //Right distance!
                    if ($distance < 1760) {
                        $this->actions[$busterId] = 'STUN ' . $ennemyId;
                        $myBuster->stun();
                        $ennemy->stunned();
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }

                    //Still too far!
                    if ($distance < 2200) {
                        $this->actions[$busterId] = 'MOVE ' . implode(' ', $ennemyCoordinates);
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }
                }
            }

            //No? ... So let's move...
            if (!array_key_exists($busterId, $this->myBusters)) {
                $this->actions[$busterId] = 'MOVE ' . mt_rand(0, 16000) . ' ' . mt_rand(0, 9000);
            } else {
                $this->actions[$busterId] = 'MOVE ' . mt_rand($this->areas[$busterId][0][0], $this->areas[$busterId][0][1]) . ' ' . mt_rand($this->areas[$busterId][1][0], $this->areas[$busterId][1][1]);
            }
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

class ExplorerGhostRemembererManager implements ManagerInterface
{
    const ENTITYTYPE_TEAM0 = 0;
    const ENTITYTYPE_TEAM1 = 1;
    const ENTITYTYPE_GHOST = -1;

    protected $bustersPerPlayer;
    protected $ghostCount;
    protected $myTeamId;
    /** @var MapInterface */
    protected $map;

    protected $areas;

    /** @var BusterInterface[] */
    protected $myBusters;
    /** @var BusterInterface[] */
    protected $hisBusters;
    /** @var GhostInterface[] */
    protected $ghosts;

    /** @var string[] */
    protected $actions;

    /** @var GhostInterface[] */
    protected $ghostsISaw;

    public function __construct($bustersPerPlayer, $ghostCount, $myTeamId, $map)
    {
        $this->bustersPerPlayer = $bustersPerPlayer;
        $this->ghostCount = $ghostCount;
        $this->myTeamId = $myTeamId;
        $this->map = $map;

        $this->myBusters = [];

        $this->defineAreas();
    }

    private function defineAreas()
    {
        if ($this->bustersPerPlayer == 2) {
            $this->areas = [
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 3) {
            $this->areas = [
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 4) {
            $this->areas = [
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
            ];
        }
    }

    public function newTurn()
    {
        $this->ghosts = [];
        $this->hisBusters = [];
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
            return $this->getEnnemy($entityId);
        }

        //Look for ally?
        return $this->getAlly($entityId);
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    protected function getEnnemy($entityId)
    {
        return array_key_exists($entityId, $this->hisBusters) ? $this->hisBusters[$entityId] : false;
    }

    protected function getAlly($entityId)
    {
        return array_key_exists($entityId, $this->myBusters) ? $this->myBusters[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        //Ennemy's buster
        if ($this->myTeamId !== $entityType) {
            $this->hisBusters[$entityId] = $entity;
            return;
        }

        //My buster
        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
    }

    protected function fixCoordinates($coordinates)
    {
        $coordinates[0] = max(0, min(16000, $coordinates[0]));
        $coordinates[1] = max(0, min(9000, $coordinates[1]));
        return $coordinates;
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $spottedGhosts = [];
        $stunnedEnnemy = [];

        foreach ($this->ghosts as $ghostId => $ghost) {
            $this->ghostsISaw[$ghostId] = $ghost;
        }
        unset($ghostId, $ghost);

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();
            foreach ($this->ghostsISaw as $ghostId => $ghost) {
                $ghostCoordinates = $ghost->getCoordinates();
                if (Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]) < 500) {
                    unset($this->ghostsISaw[$ghostId]);
                }
            }
        }
        unset($busterId, $buster, $busterCoordinates, $ghostId, $ghost, $ghostCoordinates);

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();

            //Ghost in bag? Go home and release ghost.
            if ($myBuster->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $myBuster->release();
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
                    $nextBusterCoordinates = $busterCoordinates;
                    $nextBusterCoordinates[0] += mt_rand(-600, 600);
                    $nextBusterCoordinates[1] += mt_rand(-600, 600);
                    $nextBusterCoordinates = $this->fixCoordinates($nextBusterCoordinates);
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $nextBusterCoordinates);
                    unset($nextBusterCoordinates);
                    continue 2;
                }

                //Right distance!
                if ($distance < 1760) {
                    $this->actions[$busterId] = 'BUST ' . $ghostId;
                    $myBuster->bust();
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

            if (!$myBuster->hasStunned()) {
                //Is there a closed enough ennemy to stun him?
                foreach ($this->hisBusters as $ennemyId => $ennemy) {
                    if ($ennemy->isStunned() || array_key_exists($ennemyId, $stunnedEnnemy)) {
                        continue;
                    }

                    $ennemyCoordinates = $ennemy->getCoordinates();
                    $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ennemyCoordinates[0], $ennemyCoordinates[1]);

                    //Right distance!
                    if ($distance < 1760) {
                        $this->actions[$busterId] = 'STUN ' . $ennemyId;
                        $myBuster->stun();
                        $ennemy->stunned();
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }

                    //Still too far!
                    if ($distance < 2200) {
                        $this->actions[$busterId] = 'MOVE ' . implode(' ', $ennemyCoordinates);
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }
                }
            }
        }

        foreach ($this->myBusters as $busterId => $buster) {
            if (array_key_exists($busterId, $this->actions)) {
                continue;
            }

            foreach ($this->ghostsISaw as $ghostId => $ghost) {
                $ghostCoordinates = $ghost->getCoordinates();
                $closestGhostId = false;
                $closestGhostDistance = false;
                $currentDistance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]);
                if ($closestGhostId === false || $currentDistance < $closestGhostDistance) {

                }
            }

            //Nothing to do? So let's move...
            if (!array_key_exists($busterId, $this->myBusters)) {
                $this->actions[$busterId] = 'MOVE ' . mt_rand(0, 16000) . ' ' . mt_rand(0, 9000);
            } else {
                $this->actions[$busterId] = 'MOVE ' . mt_rand($this->areas[$busterId][0][0], $this->areas[$busterId][0][1]) . ' ' . mt_rand($this->areas[$busterId][1][0], $this->areas[$busterId][1][1]);
            }
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

class BronzeExplorerGhostRemembererManager implements ManagerInterface
{
    const ENTITYTYPE_TEAM0 = 0;
    const ENTITYTYPE_TEAM1 = 1;
    const ENTITYTYPE_GHOST = -1;

    protected $bustersPerPlayer;
    protected $ghostCount;
    protected $myTeamId;
    /** @var MapInterface */
    protected $map;

    protected $areas;

    /** @var BusterInterface[] */
    protected $myBusters;
    /** @var BusterInterface[] */
    protected $hisBusters;
    /** @var GhostInterface[] */
    protected $ghosts;

    /** @var string[] */
    protected $actions;

    /** @var GhostInterface[] */
    protected $ghostsISaw;

    public function __construct($bustersPerPlayer, $ghostCount, $myTeamId, $map)
    {
        $this->bustersPerPlayer = $bustersPerPlayer;
        $this->ghostCount = $ghostCount;
        $this->myTeamId = $myTeamId;
        $this->map = $map;

        $this->myBusters = [];

        $this->defineAreas();
    }

    private function defineAreas()
    {
        if ($this->bustersPerPlayer == 2) {
            $this->areas = [
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
                [[0, 8500], [0, 9000]],
                [[7501, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 3) {
            $this->areas = [
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
                [[0, 5500], [0, 9000]],
                [[5201, 10600], [0, 9000]],
                [[9801, 16000], [0, 9000]],
            ];
        } elseif ($this->bustersPerPlayer === 4) {
            $this->areas = [
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
                [[0, 8500], [0, 5000]],
                [[0, 8500], [4000, 9000]],
                [[7501, 16000], [0, 5000]],
                [[5701, 16000], [4000, 9000]],
            ];
        }
    }

    public function newTurn()
    {
        $this->ghosts = [];
        $this->hisBusters = [];
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
            return $this->getEnnemy($entityId);
        }

        //Look for ally?
        return $this->getAlly($entityId);
    }

    protected function getGhost($entityId)
    {
        return array_key_exists($entityId, $this->ghosts) ? $this->ghosts[$entityId] : false;
    }

    protected function getEnnemy($entityId)
    {
        return array_key_exists($entityId, $this->hisBusters) ? $this->hisBusters[$entityId] : false;
    }

    protected function getAlly($entityId)
    {
        return array_key_exists($entityId, $this->myBusters) ? $this->myBusters[$entityId] : false;
    }

    public function updateEntity($entityType, $entityId, EntityInterface $entity)
    {
        //Ghost
        if (static::ENTITYTYPE_GHOST === $entityType) {
            $this->ghosts[$entityId] = $entity;
            return;
        }

        //Ennemy's buster
        if ($this->myTeamId !== $entityType) {
            $this->hisBusters[$entityId] = $entity;
            return;
        }

        //My buster
        //Update or create?
        if (array_key_exists($entityId, $this->myBusters)) {
            $this->myBusters[$entityId]->update($entity);
        } else {
            $this->myBusters[$entityId] = $entity;
        }
    }

    protected function fixCoordinates($coordinates)
    {
        $coordinates[0] = max(0, min(16000, $coordinates[0]));
        $coordinates[1] = max(0, min(9000, $coordinates[1]));
        return $coordinates;
    }

    public function analyse()
    {
        $baseCoordinates = $this->map->getBaseCoordinates();
        $stunnedEnnemy = [];

        foreach ($this->ghosts as $ghostId => $ghost) {
            $this->ghostsISaw[$ghostId] = $ghost;
        }
        unset($ghostId, $ghost);

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();
            foreach ($this->ghostsISaw as $ghostId => $ghost) {
                $ghostCoordinates = $ghost->getCoordinates();
                if (Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]) < 500) {
                    unset($this->ghostsISaw[$ghostId]);
                }
            }
        }
        unset($busterId, $buster, $busterCoordinates, $ghostId, $ghost, $ghostCoordinates);

        foreach ($this->myBusters as $busterId => $myBuster) {
            $busterCoordinates = $myBuster->getCoordinates();

            //Ghost in bag? Go home and release ghost.
            if ($myBuster->hasBusted()) {
                if (Rule::getDistance($baseCoordinates[0], $baseCoordinates[1], $busterCoordinates[0], $busterCoordinates[1]) <= 1600) {
                    $this->actions[$busterId] = 'RELEASE';
                    $myBuster->release();
                    continue;
                }

                $this->actions[$busterId] = 'MOVE ' . implode(' ', $baseCoordinates);
                continue;
            }

            //Is there a closed enough ghost?
            foreach ($this->ghosts as $ghostId => $ghost) {
                $ghostCoordinates = $ghost->getCoordinates();
                $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]);

                //Too close!
                if ($distance < 900) {
                    $nextBusterCoordinates = $busterCoordinates;
                    $nextBusterCoordinates[0] += mt_rand(-600, 600);
                    $nextBusterCoordinates[1] += mt_rand(-600, 600);
                    $nextBusterCoordinates = $this->fixCoordinates($nextBusterCoordinates);
                    $this->actions[$busterId] = 'MOVE ' . implode(' ', $nextBusterCoordinates);
                    unset($nextBusterCoordinates);
                    continue 2;
                }

                //Right distance!
                if ($distance < 1760) {
                    $this->actions[$busterId] = 'BUST ' . $ghostId;
                    $myBuster->bust();
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

            if (!$myBuster->hasStunned()) {
                //Is there a closed enough ennemy to stun him?
                foreach ($this->hisBusters as $ennemyId => $ennemy) {
                    if ($ennemy->isStunned() || array_key_exists($ennemyId, $stunnedEnnemy)) {
                        continue;
                    }

                    $ennemyCoordinates = $ennemy->getCoordinates();
                    $distance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ennemyCoordinates[0], $ennemyCoordinates[1]);

                    //Right distance!
                    if ($distance < 1760) {
                        $this->actions[$busterId] = 'STUN ' . $ennemyId;
                        $myBuster->stun();
                        $ennemy->stunned();
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }

                    //Still too far!
                    if ($distance < 2200) {
                        $this->actions[$busterId] = 'MOVE ' . implode(' ', $ennemyCoordinates);
                        $stunnedEnnemy[$ennemyId] = true;
                        continue 2;
                    }
                }
            }
        }

        foreach ($this->myBusters as $busterId => $buster) {
            if (array_key_exists($busterId, $this->actions)) {
                continue;
            }

            $busterCoordinates = $buster->getCoordinates();

            /*foreach ($this->ghostsISaw as $ghostId => $ghost) {
                $ghostCoordinates = $ghost->getCoordinates();
                $closestGhostId = false;
                $closestGhostDistance = false;
                $currentDistance = Rule::getDistance($busterCoordinates[0], $busterCoordinates[1], $ghostCoordinates[0], $ghostCoordinates[1]);
                if ($closestGhostId === false || $currentDistance < $closestGhostDistance) {
                    $closestGhostId = $ghostId;
                    $closestGhostDistance = $currentDistance;
                }
            }
            if (isset($closestGhostId)) {
                $this->actions[$busterId] = 'MOVE ' . implode(' ', $this->getGhost($closestGhostId)->getCoordinates());
                continue;
            }*/

            //Nothing to do? So let's move...
            if (!array_key_exists($busterId, $this->myBusters)) {
                $this->actions[$busterId] = 'MOVE ' . mt_rand(0, 16000) . ' ' . mt_rand(0, 9000);
            } else {
                $this->actions[$busterId] = 'MOVE ' . mt_rand($this->areas[$busterId][0][0], $this->areas[$busterId][0][1]) . ' ' . mt_rand($this->areas[$busterId][1][0], $this->areas[$busterId][1][1]);
            }
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

    public function update(EntityInterface $entity);
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

    public function update(EntityInterface $entity)
    {
        return false;
    }
}

interface BusterInterface extends EntityInterface
{
    public function bust();
    public function hasBusted();
    public function release();
    public function stun();
    public function hasStunned();
    public function stunned();
    public function isStunned();
}

class DummyBuster implements BusterInterface
{

    protected $id;
    protected $type;
    protected $coordinates;
    protected $busted = false;
    protected $stun;
    protected $stunned;

    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
        $this->stun = 0;
        $this->stunned = 0;
    }

    public function newTurn($x, $y, $state, $value)
    {
        $this->coordinates = [$x, $y];
        $this->busted = $value !== -1;
        --$this->stun;
        --$this->stunned;
    }

    public function getCoordinates()
    {
        return $this->coordinates;
    }

    public function update(EntityInterface $entity)
    {
        $this->coordinates = $entity->getCoordinates();
        $this->busted = $this->hasBusted();
        $this->stun = $this->remainingTurnsBeforeNextStun();
        $this->stunned = $this->remainingStunnedTurns();
    }

    public function bust()
    {
        $this->busted = true;
    }

    /**
     * @return boolean
     */
    public function hasBusted()
    {
        return $this->busted;
    }

    public function release()
    {
        $this->busted = false;
    }

    public function stun()
    {
        $this->stun = 20;
    }

    public function hasStunned()
    {
        return $this->stun > 0;
    }

    public function remainingTurnsBeforeNextStun()
    {
        return $this->stun;
    }

    public function stunned()
    {
        $this->stunned = 10;
    }

    public function isStunned()
    {
        return $this->stunned > 0;
    }

    public function remainingStunnedTurns()
    {
        return $this->stunned;
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
$manager = new ExploreRandomAreasManager($bustersPerPlayer, $ghostCount, $myTeamId, $map);

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
