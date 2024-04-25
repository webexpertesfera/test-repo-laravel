<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Wishlists\WishlistRepository;
use App\Http\Requests\Wishlists\CreateWishlistRequest;
use App\Http\Resources\WishlistResource;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public $wishlist;

    public function __construct(WishlistRepository $wishlist)
    {
        $this->wishlist = $wishlist;
    }

    public function getMyWishlist()
    {
        $wishlists = $this->wishlist->myWishlist();

        return WishlistResource::collection($wishlists);
    }

    public function create(CreateWishlistRequest $request)
    {
        $data = $request->getAndFormatData();

        $wishlist = $this->wishlist->create($data);

        return WishlistResource::make($wishlist);
    }

    public function delete($id)
    {
        $wishlist = $this->wishlist->delete($id);

        return WishlistResource::make($wishlist);
    }
}
