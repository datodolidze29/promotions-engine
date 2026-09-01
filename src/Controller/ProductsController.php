<?php

namespace App\Controller;

use App\DTO\LowestPriceEnquiry;
use App\Entity\Promotion;
use App\Filter\PromotionsFilterInterface;
use App\Repository\ProductRepository;
use App\Service\Serializer\DTOSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
    public function __construct(private ProductRepository $repository, private EntityManagerInterface $entityManager) {}

    #[Route("/products/{id}/lowest-price", name: "lowest_price", methods: ["POST"])]
    public function lowestPrice(
        Request $request,
        int $id,
        DTOSerializer $serializer,
        PromotionsFilterInterface $promotionsFilter, //we only have one class that implements promotionsFilterInterface so Under the hood and symfony find one and only class that is implementing it
    ): Response {
        if ($request->headers->has("force_fail")) {
            return new JsonResponse(
                [
                    "error" => "failure",
                ],
                $request->headers->get("force_fail"),
            );
        }

        // 1) deserialize the data into data transfer object (DTO)
        $lowestPriceEnquiry = $serializer->deserialize($request->getContent(), LowestPriceEnquiry::class, "json");

        $product = $this->repository->find($id); //add error handling

        $lowestPriceEnquiry->setProduct($product);

        $promotions = $this->entityManager
            ->getRepository(Promotion::class)
            ->findValidForProduct($product, date_create_immutable($lowestPriceEnquiry->getRequestDate()));

        // 2) pass the enquiry into promo filter and appropriate filter be applied (Enquiry -  act of asking for information or a request to find out more about something)
        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry, ...$promotions);
        // 3) return modified enquiry

        $responseContent = $serializer->serialize($modifiedEnquiry, "json");

        return new Response($responseContent, 200, ["Content-Type" => "application/json"]);
    }
}
