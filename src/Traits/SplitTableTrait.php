<?php

namespace ZhenMu\Support\Traits;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * model 设置
 */
// class WechatBill extends Model
// {
//     use HasFactory;

//     use SplitTableTrait;

//     protected $table = 'wechat_bill';

//     protected $guarded = [];

//     public function __construct(array $attributes = [], $suffix = null)
//     {
//         // 初始化分表处理
//         $this->init($attributes, $suffix);

//         parent::__construct($attributes);
//     }
// }


/**
 * 分表查询示例
 */
// $wechatBill = new WechatBill();
// $wechatBill->setSuffix(202303);
// return $wechatBill->newQuery()->get();


/**
 * 分表写入
 */
// return (new WechatBill([], 202303))->newInstance()->create([]);


trait SplitTableTrait
{
    /**
     * 是否分表，默认false，即不分表
     * @var bool
     */
    protected $isSplitTable = true;

    /**
     * 最终生成表
     * @var
     */
    protected $endTable;

    /**
     * 后缀参数
     * @var null
     */
    protected $suffix = null;

    /**
     * 初始化分表处理
     * @param  array  $attributes
     * @param $suffix
     * @return void
     */
    public function init(array $attributes = [], $suffix = null)
    {
        $this->endTable = $this->table;

        // isSplitTable参数为true时进行分表，否则不分表
        if ($this->isSplitTable) {
            // 初始化后缀，未传则默认年月分表
            $this->suffix = $suffix ?: Carbon::now()->format('Ym');
        }
        //初始化分表表名并创建
        $this->setSuffix($suffix);
    }

    /**
     * 设置表后缀, 如果设置分表后缀，可在service层调用生成自定义后缀表名，
     * 但每次操作表之前都需要调用该方法以保证数据表的准确性
     * @param $suffix
     * @return void
     */
    public function setSuffix($suffix = null)
    {
        // isSplitTable参数为true时进行分表，否则不分表
        if ($this->isSplitTable) {
            //初始化后缀，未传则默认年月分表
            $this->suffix = $suffix ?: Carbon::now()->format('Ym');
        }

        if ($this->suffix !== null) {
            // 最终表替换模型中声明的表作为分表使用的表
            $this->table = $this->endTable.'_'.$this->suffix;
        }

        // 调用时，创建分表，格式为 table_{$suffix}
        // 未传自定义后缀情况下,，默认按年月分表格式为：orders_202205
        // 无论使用时是否自定义分表名，都会创建默认的分表，除非关闭该调用
        $this->createTable();
    }

    /**
     * 提供一个静态方法设置表后缀
     * @param $suffix
     * @return mixed
     */
    public static function suffix($suffix = null)
    {
        $instance = new static;
        $instance->setSuffix($suffix);

        return $instance->newQuery();
    }

    /**
     * 创建新的"table_{$suffix}"的模型实例并返回
     * @param  array  $attributes
     * @return object $model
     */
    public function newInstance($attributes = [], $exists = false): object
    {
        $model = parent::newInstance($attributes, $exists);
        $model->setSuffix($this->suffix);

        return $model;
    }

    /**
     * 创建分表，没有则创建，有则不处理
     * @return void
     */
    protected function createTable()
    {
        $connectName = $this->getConnectionName();

        // 初始化分表,，按年月分表格式为：orders_202205
        if (!Schema::connection($connectName)->hasTable($this->table)) {
            Schema::connection($connectName)->create($this->table, function (Blueprint $table) {
                $this->migrationUp($table);
            });
        }
    }
  
    abstract public function migrationUp(Blueprint $table): void;
}
