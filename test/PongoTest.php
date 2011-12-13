<?php
namespace pongo;

require_once realpath(__DIR__) . '/helper.php';

class PongoTest extends \PHPUnit_Framework_TestCase {
    public function setUp() 
    {
        $this->clean();
    }

    public function tearDown()
    {
        $this->clean();
    }

    private function clean()
    {
        User::drop();
    }

    public function testInsert()
    {
        $uid = time();

        $user = new User(array(
            '_id'   => $uid,
            'name'  => 'Zhou Xianhua',
            'age'   => 10
        ));
        $user->insert();
        $this->assertEquals(1, User::count());

        $user = new User(array(
            '_id'   => $uid,
            'name'  => 'Zhou2 Xianhua',
            'age'   => 10
        ));
        $r = $user->insert();
        $this->assertEquals(1, User::count());

        $u = User::findOne(array('_id' => $uid));
        $this->assertEquals('Zhou Xianhua', $u->name);
    }

    public function testSave()
    {
        $user = new User(array(
            '_id'   => time(),
            'name'  => 'Zhou Xianhua',
            'age'   => 10
        ));
        $user->save();
        $this->assertEquals(1, User::count());
    }

    public function testUpdate()
    {
        $uid = time();

        $user = new User(array(
            '_id'   => $uid,
            'name'  => 'Zhou Xianhua',
            'age'   => 10
        ));
        $user->save();

        // update
        $user->name = 'Changed Name';
        $user->save();

        $this->assertEquals(1, User::count());

        $u = User::findOne(array('_id' => $uid));
        $this->assertEquals('Changed Name', $u->name);
        $this->assertEquals(10, $u->age);
    }

    public function testRemove()
    {
        $user1 = new User(array(
            '_id'   => 1,
            'name'  => 'User A',
            'age'   => 10
        ));
        $user1->save();

        $user2 = new User(array(
            '_id'   => 2,
            'name'  => 'User B',
            'age'   => 11
        ));
        $user2->save();

        $this->assertEquals(2, User::count());

        $user1->remove();

        $this->assertEquals(1, User::count());
        $u = User::findOne(array('_id' => 2));
        $this->assertEquals('User B', $u->name);
    }

    public function testFind()
    {
        $users = array(
           array('name' => 'User 1', 'age' => 10),
           array('name' => 'User 2', 'age' => 20),
           array('name' => 'User 3', 'age' => 30),
        );

        User::collection()->batchInsert($users);

        $results = User::find(array(
            'conditions' => array('age' => 21)
        ));
        $this->assertEquals(0, count($results));

        $results = User::find();
        $this->assertEquals(3, count($results));

        $results = User::find(array('limit' => 2));
        $this->assertEquals(2, count($results));

        $results = User::find(array(
            'conditions' => array('age' => array('$gte' => 20))
        ));
        $this->assertEquals(2, count($results));

        $results = User::find(array(
            'conditions' => array('age' => 20)
        ));
        $this->assertEquals(1, count($results));

        $this->assertEquals('User 2', $results[0]->name);

        $results = User::find(array(
            'conditions' => array('age' => 20)
        ));
        $this->assertEquals(1, count($results));
    }

    public function testMongoFind() 
    {
        $users = array(
           array('name' => 'User 1', 'age' => 10),
           array('name' => 'User 2', 'age' => 20),
           array('name' => 'User 3', 'age' => 30),
        );
        User::collection()->batchInsert($users);

        $this->assertTrue(User::mongoFind() instanceof \MongoCursor);

        $results = User::asObjects(User::mongoFind(array('age' => 20))->limit(1));
        $this->assertEquals(1, count($results));
    }

    public function testGroup()
    {
        $users = array(
           array('name' => 'User 5', 'age' => 22),
           array('name' => 'User 2', 'age' => 10),
           array('name' => 'User 3', 'age' => 10),
           array('name' => 'User 4', 'age' => 21),
           array('name' => 'User 1', 'age' => 10),
        );
        User::collection()->batchInsert($users);

        $keys = array('age' => 1);
        $initial = array('total' => 0);
        $reduce = 'function (obj, prev){ prev.total += 1; }';
        $results = User::group($keys, $initial, $reduce);

        $this->assertEquals(3, count($results));

        foreach ($results as $result) {
            if ($result['age'] == 10) {
                $this->assertEquals(3, $result['total']);
            }
            if ($result['age'] == 21) {
                $this->assertEquals(1, $result['total']);
            }
        }
    }

    public function testCollection() 
    {
        $this->assertTrue(User::collection() instanceof \MongoCollection);
    }

    public function testDB() 
    {
        $this->assertTrue(User::DB() instanceof \MongoDB);
    }
}
