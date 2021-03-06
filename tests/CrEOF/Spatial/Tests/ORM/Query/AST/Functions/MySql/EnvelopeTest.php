<?php
/**
 * Copyright (C) 2012 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Spatial\Tests\ORM\Functions\MySql;

use CrEOF\Spatial\PHP\Types\Geometry\LineString;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use CrEOF\Spatial\PHP\Types\Geometry\Polygon;
use CrEOF\Spatial\Tests\Fixtures\PolygonEntity;
use CrEOF\Spatial\Tests\OrmTest;
use Doctrine\ORM\Query;

/**
 * Envelope DQL function tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 *
 * @group common
 */
class EnvelopeTest extends OrmTest
{
    public function testSelectEnvelope()
    {
        if ($this->getPlatform()->getName() == 'postgresql') {
            $this->markTestSkipped('Function not supported on postgresql.');
        }

        $entity1 = new PolygonEntity();
        $rings1 = array(
            new LineString(array(
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0)
            ))
        );

        $entity1->setPolygon(new Polygon($rings1));
        $this->_em->persist($entity1);

        $entity2 = new PolygonEntity();
        $rings2 = array(
            new LineString(array(
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0)
            )),
            new LineString(array(
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5)
            ))
        );

        $entity2->setPolygon(new Polygon($rings2));
        $this->_em->persist($entity2);
        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT AsText(Envelope(p.polygon)) FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p');
        $result = $query->getResult();

        $this->assertEquals('POLYGON((0 0,10 0,10 10,0 10,0 0))', $result[0][1]);
        $this->assertEquals('POLYGON((0 0,10 0,10 10,0 10,0 0))', $result[1][1]);
    }

    public function testEnvelopeWhereParameter()
    {
        if ($this->getPlatform()->getName() == 'postgresql') {
            $this->markTestSkipped('Function not supported on postgresql.');
        }

        $entity1 = new PolygonEntity();
        $rings1 = array(
            new LineString(array(
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0)
            )),
            new LineString(array(
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5)
            ))
        );

        $entity1->setPolygon(new Polygon($rings1));
        $this->_em->persist($entity1);

        $entity2 = new PolygonEntity();
        $rings2 = array(
            new LineString(array(
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5)
            ))
        );

        $entity2->setPolygon(new Polygon($rings2));
        $this->_em->persist($entity2);
        $this->_em->flush();
        $this->_em->clear();

        $query        = $this->_em->createQuery('SELECT p FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p WHERE Envelope(p.polygon) = GeomFromText(:p1)');
        $envelopeRing = new LineString(array(
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0)
            )
        );
        $envelope = new Polygon(array($envelopeRing));

        $query->setParameter('p1', $envelope, 'polygon');

        $result = $query->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($entity1, $result[0]);
    }
}
