<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\AdminPermission;
use App\Request\MenuRequest;
use App\Resource\MenuResource;
use Donjan\Casbin\Enforcer;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;
use App\Middleware\PermissionMiddleware;

/**
 * Class MenuController
 * @package App\Controller\Admin
 * @Controller()
 * @Middlewares({
 *     @Middleware(JWTAuthMiddleware::class),
 *     @Middleware(PermissionMiddleware::class)
 * })
 */
class MenuController extends AbstractController
{
    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @RequestMapping(path="index", methods="get")
     */
    public function index()
    {
        $id = $this->request->input('id', '%');
        $name = $this->request->input('name', '%');
        $status = $this->request->input('status', '%');
        $p_id = $this->request->input('p_id', '%');
        $menu = $this->request->input('menu', 1);
        $pageSize = $this->request->query('pageSize') ?? 10;
        $pageNo = $this->request->query('pageNo') ?? 1;

        $permission = AdminPermission::query()
            ->where([
                ['id', 'like', $id],
                ['name', 'like', $name],
                ['status', 'like', $status],
                ['p_id', '>=', $p_id],
                ['is_menu', '=', $menu]
            ])
            ->paginate((int) $pageSize, ['*'], 'page', (int) $pageNo);
        $permissions = $permission->toArray();

        $data = [
            'pageSize' => $permissions['per_page'],
            'pageNo' => $permissions['current_page'],
            'totalCount' => $permissions['total'],
            'totalPage' => $permissions['to'],
            'data' => MenuResource::collection($permission),
        ];
        return $this->success($data);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @RequestMapping(path="create", methods="post")
     */
    public function create(MenuRequest $request)
    {
        // 验证
        $data = $request->validated();
        $data['method'] = json_encode($data['method']);
        $flag = (new MenuResource(AdminPermission::query()->create($data)))->toResponse();
        if($flag){
            return $this->success();
        }
        return $this->fail();
    }

    /**
     * @param MenuRequest $request
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     * @RequestMapping(path="update/{id}", methods="put")
     */
    public function update(MenuRequest $request, int $id)
    {
        // 验证
        $data = $request->validated();
        $data['method'] = json_encode($data['method']);
        $flag = AdminPermission::query()->where('id', $id)->update($data);
        if($flag){
            return $this->success();
        }
        return $this->fail();
    }

    /**
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     * @RequestMapping(path="edit/{id}", methods="post")
     */
    public function edit(int $id)
    {
        return $this->success($id);
    }

    /**
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     * @RequestMapping(path="delete/{id}", methods="delete")
     */
    public function delete(int $id)
    {
        if(Enforcer::getUsersForRole((string)$id)){
            return $this->fail([], '角色存在用户！');
        }
        // 判断是否存在用户角色
        if(AdminPermission::query()->where('id', $id)->delete()){
            return $this->success();
        }
        return $this->fail();
    }
}