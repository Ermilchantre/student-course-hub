<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\InterestController;
use Twig\Environment;
use App\Models\StudentInterest;
use App\Models\Programme;
use App\Models\Notification;

class InterestControllerTest extends TestCase
{
    public function testCanInstantiateInterestController()
    {
        /** @var \Twig\Environment&\PHPUnit\Framework\MockObject\MockObject $twig */
        $twig = $this->createMock(Environment::class);
        /** @var \App\Models\StudentInterest&\PHPUnit\Framework\MockObject\MockObject $interest */
        $interest = $this->createMock(StudentInterest::class);
        /** @var \App\Models\Programme&\PHPUnit\Framework\MockObject\MockObject $programme */
        $programme = $this->createMock(Programme::class);
        /** @var \App\Models\Notification&\PHPUnit\Framework\MockObject\MockObject $notification */
        $notification = $this->createMock(Notification::class);

        $controller = new InterestController($twig, $interest, $programme, $notification);
        $this->assertInstanceOf(InterestController::class, $controller);
    }
}
