<?php

define('DEBUG', true);
function _($var)
{
    if (defined('DEBUG') && DEBUG) {
        error_log(var_export($var, true));
    }
}

final class Coordinates
{
    private $x;
    private $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }
}

interface PodInterface
{
    public function setCoordinates(Coordinates $coordinates): self;
    public function getCoordinates(): ?Coordinates;
    public function getPreviousCoordinates(): ?Coordinates;

    //public function getDestination(Coordinates $checkpoint, int $distance, int $angle): Coordinates;
}

final class Pod implements PodInterface
{
    private $coordinates;
    private $previousCoordinates;

    public function setCoordinates(Coordinates $coordinates): PodInterface
    {
        $this->previousCoordinates = $this->coordinates;
        $this->coordinates = clone($coordinates);
        return $this;
    }

    public function getCoordinates(): ?Coordinates
    {
        return is_object($this->coordinates) ? clone($this->coordinates) : null;
    }

    public function getPreviousCoordinates(): ?Coordinates
    {
        return is_object($this->previousCoordinates) ? clone($this->previousCoordinates) : null;
    }
}

interface MapInterface
{
    public function getDestination(
        Coordinates $ally,
        Coordinates $ennemy,
        Coordinates $checkpoint,
        int $myDist,
        int $myAngle
    ): array;
}

final class DummyMap implements MapInterface
{
    /** @var Pod */
    private $ally;

    /** @var Pod */
    private $ennemy;

    /** @var Coordinates */
    private $checkpoint;

    public function __construct(Pod $ally, Pod $ennemy)
    {
        $this->ally = $ally;
        $this->ennemy = $ennemy;
    }

    public function getDestination(
        Coordinates $allyCoordinates,
        Coordinates $ennemyCoordinates,
        Coordinates $checkpointCoordinates,
        int $myDist,
        int $myAngle
    ): array
    {
        $this->ally->setCoordinates($allyCoordinates);
        $this->ennemy->setCoordinates($ennemyCoordinates);
        $this->checkpoint = $checkpointCoordinates;

        $abscisse = $checkpointCoordinates->getX() - $allyCoordinates->getX();
        $ordonnee = $checkpointCoordinates->getY() - $allyCoordinates->getY();
        $hyp = sqrt(pow($abscisse, 2) + pow($ordonnee, 2));
        $facteur = ($hyp - 600) / $hyp;
        $destAbscisse = $abscisse * $facteur;
        $destOrdonnee = $ordonnee * $facteur;

        if (($myAngle > -15 && $myAngle < 15) && $myDist > 5000) {
            $thrust = 'BOOST';
        } elseif ($myAngle > 135 || $myAngle < -135) {
            $thrust = 0;
        } elseif ($myAngle > 90 || $myAngle < -90) {
            $thrust = 10;
        } elseif ($myAngle > 70 || $myAngle < -70) {
            $thrust = 20;
        } else {
            /*if ($myDist < 1500) {
                $thrust = 30;
            } else*/if ($myDist < 2000) {
                $thrust = 45;
            } elseif ($myDist < 2500) {
                $thrust = 70;
            } elseif ($myDist < 3000) {
                $thrust = 85;
            } else {
                $thrust = 100;
            }
        }

        return ['x' => $allyCoordinates->getX() + round($destAbscisse), 'y' => $allyCoordinates->getY() + round($destOrdonnee), 'thrust' => $thrust];
    }
}

trait angleTrait
{
    private function getAngle(Coordinates $start, Coordinates $end): int
    {
        $abscisse = $end->getX() - $start->getX();
        $ordonnee = $end->getY() - $start->getY();
        $signeOrdonnee = 0 === $ordonnee ? 1 : $ordonnee / abs($ordonnee);
        $hyp = sqrt(pow($abscisse, 2) + pow($ordonnee, 2));
        return 0 === $hyp ? 0 : rad2deg(acos($abscisse / $hyp)) * $signeOrdonnee;
    }
}

final class Dummy2Map implements MapInterface
{
    use angleTrait;

    /** @var Pod */
    private $ally;

    /** @var Pod */
    private $ennemy;

    /** @var Coordinates */
    private $checkpoint;

    public function __construct(Pod $ally, Pod $ennemy)
    {
        $this->ally = $ally;
        $this->ennemy = $ennemy;
    }

    public function getDestination(
        Coordinates $allyCoordinates,
        Coordinates $ennemyCoordinates,
        Coordinates $checkpointCoordinates,
        int $myDist,
        int $myAngle
    ): array
    {
        $this->ally->setCoordinates($allyCoordinates);
        $this->ennemy->setCoordinates($ennemyCoordinates);
        $this->checkpoint = $checkpointCoordinates;

        $abscisse = $checkpointCoordinates->getX() - $allyCoordinates->getX();
        $ordonnee = $checkpointCoordinates->getY() - $allyCoordinates->getY();
        $hyp = sqrt(pow($abscisse, 2) + pow($ordonnee, 2));
        $facteur = ($hyp - 300) / $hyp;
        $destAbscisse = $abscisse * $facteur;
        $destOrdonnee = $ordonnee * $facteur;

        //Si l'angle du mouvement n'est pas assez proche de l'angle nécessaire pour arriver à destination,
        //alors il faut changer l'angle du pod.
        if (null !== $this->ally->getPreviousCoordinates()) {
            $angleMouvement = $this->getAngle($this->ally->getPreviousCoordinates(), $allyCoordinates);
            $angleTarget = $this->getAngle($allyCoordinates, $checkpointCoordinates);
            $ecartAngle = $angleTarget - $angleMouvement;
            if ($ecartAngle > 180) {
                $ecartAngle -= 360;
            } elseif ($ecartAngle < -180) {
                $ecartAngle += 360;
            }

            if (abs($ecartAngle) > 30 && abs($ecartAngle) < 150) {
                if (abs($ecartAngle) > 90) {
                    _('> 90');
                    $angleCompensation = (
                            $angleMouvement + 180*-1*(0 === $angleMouvement ? 1 : ($angleMouvement/abs($angleMouvement)))
                        )%180;
                    if (abs($myAngle) > 90) {
                        $r = -(abs($myAngle) - 90) * ($myAngle / abs($myAngle));
                        _('r: ' . $r);
                        $angleCompensation += $r;
                    }
                } else {
                    _('<= 90');
                    $angleCompensation = $angleTarget + $ecartAngle;
                }

                _('ECART MOUVEMENT/TARGET: ' . $ecartAngle);
                _('MOUVEMENT: ' . $angleMouvement);
                _('TARGET: ' . $angleTarget);
                _('POD: ' . $myAngle);
                _('COMPENSATION: ' . $angleCompensation);
                $destAbscisse = cos(deg2rad($angleCompensation)) * $hyp;
                $destOrdonnee = sin(deg2rad($angleCompensation)) * $hyp;
                _('New Abscisse: ' . $destAbscisse);
                _('New Ordonnee: ' . $destOrdonnee);
            }
        }

        if (abs($myAngle) < 15 && $myDist > 5000) {
            $thrust = 'BOOST';
        } elseif (abs($myAngle) > 135) {
            $thrust = 0;
        } elseif (abs($myAngle) > 90) {
            $thrust = 15;
        } elseif (abs($myAngle) > 70) {
            $thrust = 30;
        } else {
            /*if ($myDist < 1500) {
                $thrust = 30;
            } else*/if ($myDist < 2000) {
                $thrust = 45;
            } elseif ($myDist < 2500) {
                $thrust = 70;
            } elseif ($myDist < 3000) {
                $thrust = 85;
            } else {
                $thrust = 100;
            }
        }

        return ['x' => $allyCoordinates->getX() + round($destAbscisse), 'y' => $allyCoordinates->getY() + round($destOrdonnee), 'thrust' => $thrust];
    }
}

$map = new Dummy2Map(new Pod(), new Pod());

// game loop
while (TRUE)
{
    fscanf(
        STDIN,
        "%d %d %d %d %d %d",
        $x,
        $y,
        $nextCheckpointX,
        $nextCheckpointY,
        $nextCheckpointDist,
        $nextCheckpointAngle
    );
    fscanf(
        STDIN,
        "%d %d",
        $xEnnemy,
        $yEnnemy
    );

    $dest = $map->getDestination(
        new Coordinates($x, $y),
        new Coordinates($xEnnemy, $yEnnemy),
        new Coordinates($nextCheckpointX, $nextCheckpointY),
        $nextCheckpointDist,
        $nextCheckpointAngle
    );

    _('Me: ' . $x . ' ' . $y);
    _('Next checkpoint: ' . $nextCheckpointX . ' ' . $nextCheckpointY);
    _('Distance: ' . $nextCheckpointDist);
    _('Angle: ' . $nextCheckpointAngle);

    echo ($dest['x'] . ' ' . $dest['y'] . ' ' . $dest['thrust'] . ' ' . $nextCheckpointDist . ' ' . $nextCheckpointAngle . ' ' . $dest['thrust'] . "\n");
}