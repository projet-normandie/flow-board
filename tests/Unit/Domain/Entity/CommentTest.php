<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Card;
use App\Domain\Entity\Comment;
use App\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    private Comment $comment;

    protected function setUp(): void
    {
        $this->comment = new Comment();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->comment->getId());
    }

    public function testContent(): void
    {
        $this->comment->setContent('This is a comment.');
        self::assertSame('This is a comment.', $this->comment->getContent());
    }

    public function testAuthor(): void
    {
        $user = new User();
        $user->setEmail('author@test.com')->setFullName('Author');

        $this->comment->setAuthor($user);
        self::assertSame($user, $this->comment->getAuthor());
    }

    public function testCard(): void
    {
        $card = new Card();
        $card->setTitle('My Card');

        $this->comment->setCard($card);
        self::assertSame($card, $this->comment->getCard());
    }

    public function testFluentSetters(): void
    {
        $user = new User();
        $user->setEmail('author@test.com')->setFullName('Author');
        $card = new Card();
        $card->setTitle('My Card');

        $result = $this->comment
            ->setContent('Hello')
            ->setAuthor($user)
            ->setCard($card);

        self::assertSame($this->comment, $result);
    }
}
