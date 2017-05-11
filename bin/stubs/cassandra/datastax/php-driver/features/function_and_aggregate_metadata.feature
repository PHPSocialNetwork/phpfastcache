@cassandra-version-2.2
Feature: User-defined Function (UDF) and Aggregate Metadata (UDA)

  PHP Driver exposes the Cassandra Schema Metadata for UDFs and UDAs.

  Background:
    Given a running cassandra cluster
    And the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      } AND DURABLE_WRITES = false;
      USE simplex;
      CREATE OR REPLACE FUNCTION fLog (input double) CALLED ON NULL INPUT RETURNS double LANGUAGE java AS 'return Double.valueOf(Math.log(input.doubleValue()));';
      CREATE OR REPLACE FUNCTION avgState ( state tuple<int,bigint>, val int ) CALLED ON NULL INPUT RETURNS tuple<int,bigint> LANGUAGE java AS 'if (val !=null) { state.setInt(0, state.getInt(0)+1); state.setLong(1, state.getLong(1)+val.intValue()); } return state;';
      CREATE OR REPLACE FUNCTION avgFinal ( state tuple<int,bigint> ) CALLED ON NULL INPUT RETURNS double LANGUAGE java AS 'double r = 0; if (state.getInt(0) == 0) return null; r = state.getLong(1); r/= state.getInt(0); return Double.valueOf(r);';
      CREATE AGGREGATE IF NOT EXISTS average ( int ) SFUNC avgState STYPE tuple<int,bigint> FINALFUNC avgFinal INITCOND (0,0);
      """

  Scenario: Getting a function's metadata
    Given the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                        ->withContactPoints('127.0.0.1')
                        ->build();
      $session = $cluster->connect("simplex");
      $schema = $session->schema();

      $function = $schema->keyspace("simplex")->function("flog", Cassandra\Type::double());

      echo "Name: " . $function->simpleName() . "\n";
      echo "Signature: " . $function->signature() . "\n";
      echo "Language: " . $function->language() . "\n";
      echo "Body: " . $function->body() . "\n";
      echo "Arguments: " . var_export($function->arguments(), true) . "\n";
      echo "ReturnType: " . var_export($function->returnType(), true) . "\n";
      echo "IsCalledOnNullInput: " . ($function->isCalledOnNullInput() ? "true" : "false") . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: flog
      Signature: flog(double)
      Language: java
      Body: return Double.valueOf(Math.log(input.doubleValue()));
      Arguments: array (
        'input' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'double',
        )),
      )
      ReturnType: Cassandra\Type\Scalar::__set_state(array(
         'name' => 'double',
      ))
      IsCalledOnNullInput: true
      """

  @skip-ci
  Scenario: Getting an aggregates's metadata
    Given the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                        ->withContactPoints('127.0.0.1')
                        ->build();
      $session = $cluster->connect("simplex");
      $schema = $session->schema();

      $aggregate = $schema->keyspace("simplex")->aggregate("average", Cassandra\Type::int());

      echo "Name: " . $aggregate->simpleName() . "\n";
      echo "Signature: " . $aggregate->signature() . "\n";
      echo "ArgumentTypes: " . var_export($aggregate->argumentTypes(), true) . "\n";
      echo "StateType: " . var_export($aggregate->stateType(), true) . "\n";
      echo "ReturnType: " . var_export($aggregate->returnType(), true) . "\n";
      echo "InitialCondition: " . var_export($aggregate->initialCondition(), true) . "\n";
      echo "StateFunction: " .  $aggregate->stateFunction()->signature() . "\n";
      echo "FinalFunction: " .  $aggregate->finalFunction()->signature() . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: average
      Signature: average(int)
      ArgumentTypes: array (
        0 =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'int',
        )),
      )
      StateType: Cassandra\Type\Tuple::__set_state(array(
         'types' =>
        array (
          0 =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'int',
          )),
          1 =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'bigint',
          )),
        ),
      ))
      ReturnType: Cassandra\Type\Scalar::__set_state(array(
         'name' => 'double',
      ))
      InitialCondition: Cassandra\Tuple::__set_state(array(
         'type' =>
        Cassandra\Type\Tuple::__set_state(array(
           'types' =>
          array (
            0 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'int',
            )),
            1 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'bigint',
            )),
          ),
        )),
         'values' =>
        array (
          0 => 0,
          1 =>
          Cassandra\Bigint::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'bigint',
            )),
             'value' => '0',
          )),
        ),
      ))
      StateFunction: avgstate(frozen<tuple<int,bigint>>,int)
      FinalFunction: avgfinal(frozen<tuple<int,bigint>>)
      """
