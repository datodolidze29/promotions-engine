<?php

namespace App\Controller;

use App\DTO\LowestPriceEnquiry;
use App\Filter\PromotionsFilterInterface;
use App\Service\Serializer\DTOSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductsController extends AbstractController
{
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

        // 2) pass the enquiry into promo filter and appropriate filter be applied (Enquiry -  act of asking for information or a request to find out more about something)
        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry);
        // 3) return modified enquiry

        $responseContent = $serializer->serialize($modifiedEnquiry, "json");

        return new Response($responseContent, 200);
    }
}
