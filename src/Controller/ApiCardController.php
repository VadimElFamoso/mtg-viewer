<?php

namespace App\Controller;

use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Put(description: 'Return all cards in the database')]
    #[OA\Response(response: 200, description: 'List all cards')]
    public function cardAll(): Response
    {
        $this->logger->info('Listing all cards');
        $cards = $this->entityManager->getRepository(Card::class)->findAll();
        return $this->json($cards);
    }

    #[Route('/search', name: 'Search cards', methods: ['GET'])]
    #[OA\Parameter(name: 'query', description: 'Search query for card name', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Get(description: 'Search cards by name')]
    #[OA\Response(response: 200, description: 'Search results')]
    #[OA\Response(response: 404, description: 'Search card not found')]
    public function searchCards(Request $request): Response
    {
        $query = $request->query->get('query', '');
        if (strlen($query) < 3) {
            return $this->json(['error' => 'Search query must be at least 3 characters long'], 400);
        }

        $this->logger->info('Searching cards with query: ' . $query);
        $cards = $this->entityManager->getRepository(Card::class)
            ->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->json($cards);
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Put(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $this->logger->info('Showing card with UUID ' . $uuid);
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            $this->logger->error('Card with UUID ' . $uuid . ' not found');
            return $this->json(['error' => 'Card not found'], 404);
        }
        return $this->json($card);
    }
}
