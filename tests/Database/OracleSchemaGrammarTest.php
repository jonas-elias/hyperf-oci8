<?php

namespace HyperfTest\Database\Oracle\Tests\Database;

use Hyperf\Database\Connection;
use Hyperf\Database\Oracle\Schema\Grammars\OracleGrammar;
use Hyperf\Database\Oracle\Schema\OracleBlueprint as Blueprint;
use Hyperf\Database\Query\Expression;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class OracleSchemaGrammarTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateTabletetet()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'create table USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    public function getGrammar()
    {
        return new OracleGrammar();
    }

    public function testBasicCreateTableWithReservedWords()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('group');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'create table USERS ( "ID" number(10,0) not null, "GROUP" varchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(2, count($statements));
        $this->assertEquals(
            'create table USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrimaryAndForeignKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');
        $blueprint->integer('foo_id');
        $blueprint->foreign('foo_id')->references('id')->on('orders');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(3, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, "FOO_ID" number(10,0) not null, constraint users_foo_id_fk foreign key ( "FOO_ID" ) references PREFIX_ORDERS ( "ID" ), constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithNvarchar2()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->nvarchar2('first_name');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'create table USERS ( "ID" number(10,0) not null, "FIRST_NAME" nvarchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithDefaultValueAndIsNotNull()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email')->default('user@test.com');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(2, count($statements));
        $this->assertEquals(
            'create table USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) default \'user@test.com\' not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');
        $blueprint->setTablePrefix('prefix_');
        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrefixAndPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');
        $blueprint->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(2, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrefixPrimaryAndForeignKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');
        $blueprint->integer('foo_id');
        $blueprint->foreign('foo_id')->references('id')->on('orders');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');
        $blueprint->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(3, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, "FOO_ID" number(10,0) not null, constraint users_foo_id_fk foreign key ( "FOO_ID" ) references PREFIX_ORDERS ( "ID" ), constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicCreateTableWithPrefixPrimaryAndForeignKeysWithCascadeDelete()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');
        $blueprint->integer('foo_id');
        $blueprint->foreign('foo_id')->references('id')->on('orders')->onDelete('cascade');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(3, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, "FOO_ID" number(10,0) not null, constraint users_foo_id_fk foreign key ( "FOO_ID" ) references PREFIX_ORDERS ( "ID" ) on delete cascade, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicAlterTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicAlterTableWithPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicAlterTableWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setTablePrefix('prefix_');
        $blueprint->increments('id');
        $blueprint->string('email');

        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table PREFIX_USERS add ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testBasicAlterTableWithPrefixAndPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setTablePrefix('prefix_');
        $blueprint->increments('id');
        $blueprint->string('email');

        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table PREFIX_USERS add ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table USERS', $statements[0]);
    }

    public function testCompileTableExistsMethod()
    {
        $grammar = $this->getGrammar();
        $expected = 'select * from all_tables where upper(owner) = upper(?) and upper(table_name) = upper(?)';
        $sql = $grammar->compileTableExists();
        $this->assertEquals($expected, $sql);
    }

    public function testCompileColumnExistsMethod()
    {
        $grammar = $this->getGrammar();
        $expected = 'select column_name from all_tab_cols where upper(owner) = upper(\'schema\') and upper(table_name) = upper(\'test_table\')';
        $sql = $grammar->compileColumnExists('schema', 'test_table');
        $this->assertEquals($expected, $sql);
    }

    public function testDropTableWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setTablePrefix('prefix_');
        $blueprint->drop();

        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $statements = $blueprint->toSql($this->getConnection(), $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table PREFIX_USERS', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop ( "FOO" )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop ( "FOO", "BAR" )', $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop constraint foo', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop constraint foo', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop index foo', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop constraint foo', $statements[0]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS drop ( "CREATED_AT", "UPDATED_AT" )', $statements[0]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS rename to FOO', $statements[0]);
    }

    public function testRenameTableWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');
        $statements = $blueprint->toSql($this->getConnection(), $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table PREFIX_USERS rename to PREFIX_FOO', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add constraint bar primary key ("FOO")', $statements[0]);
    }

    public function testAddingPrimaryKeyWithConstraintAutomaticName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add constraint users_foo_pk primary key ("FOO")', $statements[0]);
    }

    public function testAddingPrimaryKeyWithConstraintAutomaticNameGreaterThanThirtyCharacters()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('reset_password_secret_code');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add constraint user_rese_passwor_secre_cod_pk primary key ("RESET_PASSWORD_SECRET_CODE")',
            $statements[0]
        );
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add constraint bar unique ( "FOO" )', $statements[0]);
    }

    public function testAddingDefinedUniqueKeyWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setTablePrefix('prefix_');
        $blueprint->unique('foo', 'bar');

        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $statements = $blueprint->toSql($this->getConnection(), $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table PREFIX_USERS add constraint bar unique ( "FOO" )', $statements[0]);
    }

    public function testAddingGeneratedUniqueKeyWithPrefix()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setTablePrefix('prefix_');
        $blueprint->unique('foo');

        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');

        $statements = $blueprint->toSql($this->getConnection(), $grammar);

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table PREFIX_USERS add constraint prefix_users_foo_uk unique ( "FOO" )',
            $statements[0]
        );
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));

        $this->assertEquals('create index baz on USERS ( "FOO", "BAR" )', $statements[0]);
    }

    public function testAddingForeignKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add constraint users_foo_id_fk foreign key ( "FOO_ID" ) references ORDERS ( "ID" )',
            $statements[0]
        );
    }

    public function testAddingForeignKeyWithCascadeDelete()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreign('foo_id')->references('id')->on('orders')->onDelete('cascade');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add constraint users_foo_id_fk foreign key ( "FOO_ID" ) references ORDERS ( "ID" ) on delete cascade',
            $statements[0]
        );
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "ID" number(10,0) not null, constraint users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testAddingStrindg()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" varchar2(255) not null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" varchar2(100) not null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" varchar2(100) default \'bar\' null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)
            ->nullable()
            ->default(new Expression('CURRENT TIMESTAMP'));
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "FOO" varchar2(100) default CURRENT TIMESTAMP null )',
            $statements[0]
        );
    }

    public function testAddingLongText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->longText('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" clob not null )', $statements[0]);
    }

    public function testAddingMediumText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumText('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" clob not null )', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" clob not null )', $statements[0]);
    }

    public function testAddingChar()
    {
        $blueprint = new Blueprint('users');
        $blueprint->char('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" char(255) not null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->char('foo', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" char(1) not null )', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(19,0) not null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "FOO" number(19,0) not null, constraint users_foo_pk primary key ( "FOO" ) )',
            $statements[0]
        );
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(10,0) not null )', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "FOO" number(10,0) not null, constraint users_foo_pk primary key ( "FOO" ) )',
            $statements[0]
        );
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(7,0) not null )', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(5,0) not null )', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(3,0) not null )', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(5, 2) not null )', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(5, 2) not null )', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" number(5, 2) not null )', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" char(1) not null )', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('foo', ['bar', 'baz']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "FOO" varchar2(255) not null check ("foo" in (\'bar\', \'baz\')) )',
            $statements[0]
        );
    }

    public function testAddingEnumWithDefaultValue()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('foo', ['bar', 'baz'])->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "FOO" varchar2(255) default \'bar\' not null check ("foo" in (\'bar\', \'baz\')) )',
            $statements[0]
        );
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table USERS add ( "FOO" clob not null )', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table USERS add ( "FOO" clob not null )', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" date not null )', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" date not null )', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" date not null )', $statements[0]);
    }

    public function testAddingTimeStamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" timestamp not null )', $statements[0]);
    }

    public function testAddingTimeStampTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" timestamp with time zone not null )', $statements[0]);
    }

    public function testAddingNullableTimeStamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->nullableTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "CREATED_AT" timestamp null, "UPDATED_AT" timestamp null )',
            $statements[0]
        );
    }

    public function testAddingTimeStamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "CREATED_AT" timestamp null, "UPDATED_AT" timestamp null )',
            $statements[0]
        );
    }

    public function testAddingTimeStampTzs()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals(
            'alter table USERS add ( "CREATED_AT" timestamp with time zone null, "UPDATED_AT" timestamp with time zone null )',
            $statements[0]
        );
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table USERS add ( "FOO" char(36) not null )', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table USERS add ( "FOO" blob not null )', $statements[0]);
    }

    public function testBasicCreateTableWithPrimaryAndLongForeignKeys()
    {
        $blueprint = new Blueprint('users');
        $blueprint->setMaxLength(120);
        $blueprint->setTablePrefix('prefix_');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('email');
        $blueprint->integer('very_long_foo_bar_id');
        $blueprint->foreign('very_long_foo_bar_id')->references('id')->on('orders');
        $grammar = $this->getGrammar();
        $grammar->setTablePrefix('prefix_');
        $grammar->setMaxLength(120);

        $conn = $this->getConnection();

        $statements = $blueprint->toSql($conn, $grammar);

        $this->assertEquals(3, count($statements));
        $this->assertEquals(
            'create table PREFIX_USERS ( "ID" number(10,0) not null, "EMAIL" varchar2(255) not null, "VERY_LONG_FOO_BAR_ID" number(10,0) not null, constraint prefix_users_very_long_foo_bar_id_fk foreign key ( "VERY_LONG_FOO_BAR_ID" ) references PREFIX_ORDERS ( "ID" ), constraint prefix_users_id_pk primary key ( "ID" ) )',
            $statements[0]
        );
    }

    public function testDropAllTables()
    {
        $statement = $this->getGrammar()->compileDropAllTables();

        $statements = 'BEGIN';

        $statements .= '
        FOR c IN (SELECT table_name FROM user_tables WHERE secondary = \'N\') LOOP
            EXECUTE IMMEDIATE (\'DROP TABLE "\' || c.table_name || \'" CASCADE CONSTRAINTS\');
        END LOOP;';

        $statements .= '
        FOR s IN (SELECT sequence_name FROM user_sequences) LOOP
            EXECUTE IMMEDIATE (\'DROP SEQUENCE \' || s.sequence_name);
        END LOOP;';

        $statements .= 'END;';

        $expected = $statements;

        $this->assertEquals($expected, $statement);
    }
}
