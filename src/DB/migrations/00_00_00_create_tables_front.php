<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Drop table if exist
        $this->down();
        $schema = Schema::connection(GP247_DB_CONNECTION);

        $schema->create(
            GP247_DB_PREFIX.'front_banner',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title', 255)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('url', 100)->nullable();
                $table->string('target', 50)->nullable();
                $table->text('html')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->integer('sort')->default(0);
                $table->integer('click')->default(0);
                $table->string('type', 20)->index();
                $table->timestamps();
            }
        );
        $schema->create(
            GP247_DB_PREFIX.'front_banner_type',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 100)->unique();
                $table->string('name', 255);
                $table->timestamps();
                
            }
        );


        $schema->create(
            GP247_DB_PREFIX.'front_layout_block',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 255);
                $table->string('position', 100);
                $table->string('page', 200)->nullable();
                $table->string('type', 200);
                $table->text('text')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->integer('sort')->default(0);
                $table->uuid('store_id')->default(1)->index();
                $table->string('template', '50')->index();
                $table->timestamps();
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_link',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 255);
                $table->string('url', 100)->nullable();
                $table->string('target', 100);
                $table->string('group', 100)->comment("Location of the link: footer, menu,...");
                $table->string('module', 100)->nullable()->comment("Related components (plugins, templates).\n Used to track, remove when the relevant component is removed.");
                $table->string('type', 100)->nullable()->comment("Distinguish between Link and Collection. \nValue collection|null");
                $table->string('collection_id', 100)->nullable()->comment("Collection\'s ID");
                $table->tinyInteger('status')->default(0);
                $table->integer('sort')->default(0);
                $table->timestamps();
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_link_group',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 100)->unique();
                $table->string('name', 255);
                $table->timestamps();
                
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_link_store',
            function (Blueprint $table) {
                $table->uuid('link_id');
                $table->uuid('store_id');
                $table->primary(['link_id', 'store_id']);
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_page',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('image', 255)->nullable();
                $table->string('alias', 120)->index();
                $table->integer('status')->default(0);
                $table->timestamps();
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_page_description',
            function (Blueprint $table) {
                $table->uuid('page_id');
                $table->string('lang', 10)->index();
                $table->string('title', 255)->nullable();
                $table->string('keyword', 200)->nullable();
                $table->string('description', 500)->nullable();
                $table->mediumText('content')->nullable();
                $table->unique(['page_id', 'lang']);
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_subscribe',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('email', 150)->index();
                $table->string('phone', 20)->nullable();
                $table->string('content', 300)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->uuid('store_id')->default(1)->index();
                $table->timestamps();
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_country',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 10)->unique();
                $table->string('name', 255);
            }
        );
        
        //Multi store

        $schema->create(
            GP247_DB_PREFIX.'front_banner_store',
            function (Blueprint $table) {
                $table->uuid('banner_id');
                $table->uuid('store_id');
                $table->primary(['banner_id', 'store_id']);
            }
        );

        $schema->create(
            GP247_DB_PREFIX.'front_page_store',
            function (Blueprint $table) {
                $table->uuid('page_id');
                $table->uuid('store_id');
                $table->primary(['page_id', 'store_id']);
            }
        );


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        
        $schema->dropIfExists(GP247_DB_PREFIX.'front_banner');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_banner_type');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_layout_block');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_link');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_link_group');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_page');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_page_description');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_country');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_subscribe');
        //Multi store
        $schema->dropIfExists(GP247_DB_PREFIX.'front_banner_store');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_page_store');
        $schema->dropIfExists(GP247_DB_PREFIX.'front_link_store');
        
    }
};
