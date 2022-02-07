<?php

use App\Entity\User;
use App\Entity\UserByPhone;
use App\Helper\ListConstraintViolationHelper;
use App\Repository\UserByPhoneRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExampleController
{
    /**
     * @Route("/find_or_create/user/by_phone", name="findOrCreateUserByPhone")
     * @param Request $request
     * @param UserByPhoneRepository $UserByPhoneRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function findOrCreateUserByPhone(
        Request $request,
        ValidatorInterface $validator,
        UserByPhoneRepository $UserByPhoneRepository
    ): JsonResponse {
        /*
          fetch('http://new-pre-reg.local/api/update/find_or_create/user/by_phone', {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json;charset=utf-8'
        },
        body: JSON.stringify({'UserByPhone':{name: "тестий", surname: "Тестасян", patronymic: "Тестович", phone:'79500937425',email:'e.lisin@mfc38.ru'}})
        });
         */

        $content = json_decode($request->getContent(), true);

        if (isset($content['UserByPhone'])) {

        } else {
            throw new \HttpException('not found UserByPhone parameter ');
        }
        $UserByPhone = new UserByPhone();
        $UserByPhone->fillByApiArray($content['UserByPhone']);
        $errors = $validator->validate($UserByPhone, null, [User::VALIDATOR_GROUP_CREATE_BY_PHONE]);


        if ($errors->count() > 0) {
            $ErrorHelper = new ListConstraintViolationHelper($errors);

            return new JsonResponse($ErrorHelper, JsonResponse::HTTP_BAD_REQUEST);
        }

        $UserByPhone = $UserByPhoneRepository->updateOrInsert($UserByPhone);
        UserByPhone::setGroupFieldSerialize(UserByPhone::GROUP_SERIALIZE_PUBLIC);

        return new JsonResponse($UserByPhone);

    }
}