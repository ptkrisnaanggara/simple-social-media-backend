<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FriendResource;
use App\User;
use App\Request as Req;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ReqResource;
use Validator;

class UserController extends Controller
{
    function checkUser($email)
    {
        $user = User::where(DB::raw('lower(email)'), \strtolower($email))
                        ->first();

        if(!$user) {
            $user = new User();
            $user->email = $email;
            $user->save();
        }

        return $user;
    }

    public function friendRequest(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
            'requestor' => 'required|email',
            'to' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $requestor = $this->checkUser($request->requestor);

            $to = $this->checkUser($request->to);

            $isBlock = Req::where(function($q) use($requestor) {
                            $q->where('requestor_id', $requestor->id)
                                ->OrWhere('to_id', $requestor->id);
                        })
                        ->where(function($q) use($to) {
                            $q->where('requestor_id', $to->id)
                                ->OrWhere('to_id', $to->id);
                        })
                        ->where('type', 'block')
                        ->count();

            if($isBlock > 0)
                return \response()->json(['success' => false], 200);



            $friendRequest = Req::where('requestor_id', $requestor->id)
                            ->where('to_id', $to->id)
                            ->where('type', 'friend')
                            ->first();

            if(!$friendRequest) {
                $friendRequest = new Req();
                $friendRequest->requestor_id = $requestor->id;
                $friendRequest->to_id = $to->id;
                $friendRequest->save();
            }

            DB::commit();

            return \response()->json(['success' => true], 200);

        } catch (Exception $e) {

            DB::rollBack();

            return \response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function acceptRequest(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
            'requestor' => 'required|email',
            'to' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $requestor = $this->checkUser($request->requestor);

            $to = $this->checkUser($request->to);

            $friendRequest = $this->updateRequest($requestor, $to, 'accept');

            DB::commit();

            return \response()->json(['success' => isset($friendRequest) ? true : false], 200);


        } catch (Exception $e) {
            DB::rollBack();

            return \response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }


    }

    function updateRequest($requestor, $to, $flag)
    {
        $friendRequest = Req::where('requestor_id', $requestor->id)
        ->where('to_id', $to->id)
        ->where('type', 'friend')
        ->where('status', 0)
        ->first();

        if($friendRequest) {
            $friendRequest->status = $flag == 'accept' ? 1 : 2;
            $friendRequest->save();
        }

        return $friendRequest;
    }

    public function rejectRequest(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
            'requestor' => 'required|email',
            'to' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $requestor = $this->checkUser($request->requestor);

            $to = $this->checkUser($request->to);

            $friendRequest = $this->updateRequest($requestor, $to, 'reject');

            DB::commit();

            return \response()->json(['success' => isset($friendRequest) ? true : false], 200);


        } catch (Exception $e) {
            DB::rollBack();

            return \response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function listRequest(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
            'email' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $user = $this->checkUser($request->email);
        $requests = Req::where('to_id', $user->id)->get();

        return \response()->json(['requests' => ReqResource::collection($requests)], 200);
    }

    public function listFriend(Request $request)
    {
        // dd($request->all());
        if($request->has('friends')) {
            $validator = Validator::make(
                $request->all(),
                [
                'friends' => 'required|array|max:2|min:2',
                ]
            );
        } else {
            $validator = Validator::make(
                $request->all(),
                [
                'email' => 'required|email',
                ]
            );
        }

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if($request->has('friends')) {
            $user[0] = $this->checkUser($request->friends[0])->id;
            $user[1] = $this->checkUser($request->friends[1])->id;
        } else
            $user[0] = $this->checkUser($request->email)->id;

        $friends = User::where(function($top) use ($user) {
                        $top->whereHas('to', function($q) use($user) {
                            $q->where('status',1)
                            ->where('type', 'friend')
                            ->where(function ($query) use ($user) {
                                $query->whereIn('requestor_id', $user)
                                    ->OrWhereIn('to_id', $user);
                            });
                        })
                        ->OrWhereHas('requestor', function($q) use($user) {
                            $q->where('status',1)
                            ->where('type', 'friend')
                            ->where(function ($query) use ($user) {
                                $query->whereIn('requestor_id', $user)
                                    ->OrWhereIn('to_id', $user);
                            });
                        });
                    })
                    ->WhereNotIn('id', $user)
                    ->get();
            if($request->has('friends'))
                return \response()->json(['success' => true, 'friends' => FriendResource::collection($friends), 'count' => count($friends)], 200);
            else
                return \response()->json(['friends' => FriendResource::collection($friends)], 200);

    }

    public function blockUser(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
            'requestor' => 'required|email',
            'block' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $requestor = $this->checkUser($request->requestor);

            $to = $this->checkUser($request->block);

            $userBlock = Req::where('requestor_id', $requestor->id)
                            ->where('to_id', $to->id)
                            ->where('type', 'block')
                            ->first();
            $isFriend = Req::where('requestor_id', $requestor->id)
                        ->where('to_id', $to->id)
                        ->where('type', 'friend')
                        ->first();
            if($isFriend) {
                $isFriend->status = 3;
                $isFriend->save();
            }

            if(!$userBlock) {
                $userBlock = new Req();
                $userBlock->requestor_id = $requestor->id;
                $userBlock->to_id = $to->id;
                $userBlock->status = 3;
                $userBlock->type = 'block';
                $userBlock->save();
            }

            DB::commit();

            return \response()->json(['success' => true], 200);

        } catch (Exception $e) {

            DB::rollBack();

            return \response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


}
