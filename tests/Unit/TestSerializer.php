<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use Quillstack\Serializer\Exceptions\NothingExposedException;
use Quillstack\Serializer\Serializer;
use Quillstack\Serializer\Tests\Mocks\Loop;
use Quillstack\Serializer\Tests\Mocks\Members;
use Quillstack\Serializer\Tests\Mocks\Nothing;
use Quillstack\Serializer\Tests\Mocks\Post;
use Quillstack\Serializer\Tests\Mocks\Status;
use Quillstack\Serializer\Tests\Mocks\Team;
use Quillstack\Serializer\Tests\Mocks\User;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestSerializer
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    /**
     * The one that matters: a field nobody said may go does not go.
     *
     * A serializer which sends everything but a list of exclusions sends a new column on the
     * day it is added, and says nothing about it.
     */
    public function whatNobodySaidMayGoDoesNotGo()
    {
        $result = (new Serializer())->toArray(new User());

        $this->assertBoolean->isFalse(array_key_exists('password', $result));
        $this->assertEqual->equal(
            ['id', 'email', 'created_at', 'posts', 'status'],
            array_keys($result)
        );
    }

    public function aFieldMayBeCalledSomethingElseOnTheWire()
    {
        $user = new User(createdAt: new DateTimeImmutable('2026-08-23 10:00:00', new DateTimeZone('UTC')));
        $result = (new Serializer())->toArray($user);

        $this->assertEqual->equal('2026-08-23T10:00:00+00:00', $result['created_at']);
        $this->assertBoolean->isFalse(array_key_exists('createdAt', $result));
    }

    /**
     * A field for one audience is not sent to another, so one class serves both without a
     * second one written to hide a column.
     */
    public function afieldForOneAudienceGoesToThatOne()
    {
        $user = new User(note: 'watch this one');

        $this->assertBoolean->isFalse(
            array_key_exists('note', (new Serializer())->toArray($user))
        );
        $this->assertEqual->equal(
            'watch this one',
            (new Serializer(['admin']))->toArray($user)['note']
        );
    }

    /**
     * Everything without a group is for everybody, so adding an audience does not empty what
     * was already going out.
     */
    public function whatHasNoAudienceIsForEverybody()
    {
        $result = (new Serializer(['admin']))->toArray(new User());

        $this->assertBoolean->isTrue(array_key_exists('id', $result));
        $this->assertBoolean->isTrue(array_key_exists('email', $result));
    }

    public function whatIsInsideIsSerialisedTheSameWay()
    {
        $user = new User(posts: [new Post(1, 'First'), new Post(2, 'Second')]);
        $result = (new Serializer())->toArray($user);

        $this->assertEqual->equal(
            [['id' => 1, 'title' => 'First'], ['id' => 2, 'title' => 'Second']],
            $result['posts']
        );
    }

    /**
     * Which means a field nobody exposed does not escape by being nested either.
     */
    public function nestingIsNotAWayOut()
    {
        $result = (new Serializer())->toArray(new User(posts: [new Post()]));

        $this->assertBoolean->isFalse(array_key_exists('draft', $result['posts'][0]));
    }

    /**
     * Something walked rather than held — which is what an ORM's relations are.
     */
    public function somethingWalkedIsALisT()
    {
        $team = new Team('Quillstack', new Members([new User(1), new User(2)]));
        $result = (new Serializer())->toArray($team);

        $this->assertEqual->equal([1, 2], array_column($result['members'], 'id'));
    }

    public function everyKindOfValueReadsAsItself()
    {
        $result = (new Serializer())->toArray(new User(status: Status::Suspended));

        $this->assertEqual->equal('suspended', $result['status']);
        $this->assertEqual->equal(1, $result['id']);
        $this->assertEqual->equal('ada@example.com', $result['email']);
    }

    public function nothingIsNothing()
    {
        $result = (new Serializer())->toArray(new User());

        $this->assertBoolean->isTrue($result['created_at'] === null);
        $this->assertBoolean->isTrue($result['status'] === null);
    }

    /**
     * An object with nothing exposed says so. Answering `{}` would look like an object which
     * happens to be empty, for as long as nobody noticed the attribute was missing.
     */
    public function anObjectWithNothingExposedSaysSo()
    {
        $this->assertExceptions->expect(NothingExposedException::class);

        (new Serializer())->toArray(new Nothing());
    }

    /**
     * Something pointing back at itself is a stack overflow left alone; this says so instead.
     */
    public function somethingPointingBackAtItselfIsRefused()
    {
        $loop = new Loop();
        $loop->next = $loop;

        $this->assertExceptions->expect(NothingExposedException::class);

        (new Serializer())->toArray($loop);
    }

    public function aListOfThem()
    {
        $result = (new Serializer())->toArrays([new Post(1, 'First'), new Post(2, 'Second')]);

        $this->assertEqual->equal([['id' => 1, 'title' => 'First'], ['id' => 2, 'title' => 'Second']], $result);
    }

    public function straightToJson()
    {
        $this->assertEqual->equal(
            '{"id":7,"title":"Hello"}',
            (new Serializer())->toJson(new Post(7))
        );
    }
}
