PHP_ARG_WITH(cassandra, Enable Cassandra extension,
[  --with-cassandra[=DIR]    Enable the Cassandra extension.])

AC_MSG_CHECKING([for supported PHP version])
PHP_CASSANDRA_FOUND_PHP_VERSION=`${PHP_CONFIG} --version`
PHP_CASSANDRA_FOUND_PHP_VERSION_NUMBER=`echo "${PHP_CASSANDRA_FOUND_PHP_VERSION}" | $AWK 'BEGIN { FS = "."; } { printf "%d", ([$]1 * 100 + [$]2) * 100 + [$]3;}'`
if test "$PHP_CASSANDRA_FOUND_PHP_VERSION_NUMBER" -lt "50500"; then
  AC_MSG_ERROR([not supported. PHP version 5.5.0+ required (found $PHP_CASSANDRA_FOUND_PHP_VERSION)])
else
  AC_MSG_RESULT([supported ($PHP_CASSANDRA_FOUND_PHP_VERSION)])
fi

if test -z "$PHP_UV"; then
  PHP_ARG_WITH(uv, libuv install dir,
  [  --with-uv=DIR           Specify libuv install dir.])
fi

if test -z "$PHP_GMP"; then
  PHP_ARG_WITH(gmp, GNU MP install dir,
  [  --with-gmp=DIR          Specify GNU MP install dir.])
fi

if test "$PHP_CASSANDRA" != "no"; then
  CASSANDRA_CLASSES="\
    src/Cassandra.c \
    src/Cassandra/Aggregate.c \
    src/Cassandra/BatchStatement.c \
    src/Cassandra/Bigint.c \
    src/Cassandra/Blob.c \
    src/Cassandra/Cluster.c \
    src/Cassandra/Cluster/Builder.c \
    src/Cassandra/Collection.c \
    src/Cassandra/Column.c \
    src/Cassandra/Date.c \
    src/Cassandra/Decimal.c \
    src/Cassandra/DefaultAggregate.c \
    src/Cassandra/DefaultCluster.c \
    src/Cassandra/DefaultColumn.c \
    src/Cassandra/DefaultFunction.c \
    src/Cassandra/DefaultIndex.c \
    src/Cassandra/DefaultKeyspace.c \
    src/Cassandra/DefaultMaterializedView.c \
    src/Cassandra/DefaultSchema.c \
    src/Cassandra/DefaultSession.c \
    src/Cassandra/DefaultTable.c \
    src/Cassandra/Exception.c \
    src/Cassandra/ExecutionOptions.c \
    src/Cassandra/Float.c \
    src/Cassandra/Function.c \
    src/Cassandra/Future.c \
    src/Cassandra/FutureClose.c \
    src/Cassandra/FuturePreparedStatement.c \
    src/Cassandra/FutureRows.c \
    src/Cassandra/FutureSession.c \
    src/Cassandra/FutureValue.c \
    src/Cassandra/Index.c \
    src/Cassandra/Inet.c \
    src/Cassandra/Keyspace.c \
    src/Cassandra/Map.c \
    src/Cassandra/MaterializedView.c \
    src/Cassandra/Numeric.c \
    src/Cassandra/PreparedStatement.c \
    src/Cassandra/RetryPolicy.c \
    src/Cassandra/Rows.c \
    src/Cassandra/Schema.c \
    src/Cassandra/Session.c \
    src/Cassandra/Set.c \
    src/Cassandra/SimpleStatement.c \
    src/Cassandra/Smallint.c \
    src/Cassandra/SSLOptions.c \
    src/Cassandra/SSLOptions/Builder.c \
    src/Cassandra/Statement.c \
    src/Cassandra/Table.c \
    src/Cassandra/Time.c \
    src/Cassandra/Timestamp.c \
    src/Cassandra/TimestampGenerator.c \
    src/Cassandra/TimestampGenerator/Monotonic.c \
    src/Cassandra/TimestampGenerator/ServerSide.c \
    src/Cassandra/Timeuuid.c \
    src/Cassandra/Tinyint.c \
    src/Cassandra/Tuple.c \
    src/Cassandra/Type.c \
    src/Cassandra/UserTypeValue.c \
    src/Cassandra/Uuid.c \
    src/Cassandra/UuidInterface.c \
    src/Cassandra/Value.c \
    src/Cassandra/Varint.c \
  ";

  CASSANDRA_EXCEPTIONS="\
    src/Cassandra/Exception/AlreadyExistsException.c \
    src/Cassandra/Exception/AuthenticationException.c \
    src/Cassandra/Exception/ConfigurationException.c \
    src/Cassandra/Exception/DivideByZeroException.c \
    src/Cassandra/Exception/DomainException.c \
    src/Cassandra/Exception/ExecutionException.c \
    src/Cassandra/Exception/InvalidArgumentException.c \
    src/Cassandra/Exception/InvalidQueryException.c \
    src/Cassandra/Exception/InvalidSyntaxException.c \
    src/Cassandra/Exception/IsBootstrappingException.c \
    src/Cassandra/Exception/LogicException.c \
    src/Cassandra/Exception/OverloadedException.c \
    src/Cassandra/Exception/ProtocolException.c \
    src/Cassandra/Exception/RangeException.c \
    src/Cassandra/Exception/ReadTimeout.c \
    src/Cassandra/Exception/RuntimeException.c \
    src/Cassandra/Exception/ServerException.c \
    src/Cassandra/Exception/TimeoutException.c \
    src/Cassandra/Exception/TruncateException.c \
    src/Cassandra/Exception/UnauthorizedException.c \
    src/Cassandra/Exception/UnavailableException.c \
    src/Cassandra/Exception/UnpreparedException.c \
    src/Cassandra/Exception/ValidationException.c \
    src/Cassandra/Exception/WriteTimeoutException.c
  ";

  CASSANDRA_RETRY_POLICIES="\
    src/Cassandra/RetryPolicy/DefaultPolicy.c \
    src/Cassandra/RetryPolicy/DowngradingConsistency.c \
    src/Cassandra/RetryPolicy/Fallthrough.c \
    src/Cassandra/RetryPolicy/Logging.c
  ";

  CASSANDRA_TYPES="\
    src/Cassandra/Type/Collection.c \
    src/Cassandra/Type/Custom.c \
    src/Cassandra/Type/Map.c \
    src/Cassandra/Type/Scalar.c \
    src/Cassandra/Type/Set.c \
    src/Cassandra/Type/Tuple.c \
    src/Cassandra/Type/UserType.c
  ";

  CASSANDRA_UTIL="\
    util/bytes.c \
    util/collections.c \
    util/consistency.c \
    util/future.c \
    util/hash.c \
    util/inet.c \
    util/math.c \
    util/ref.c \
    util/result.c \
    util/types.c \
    util/uuid_gen.c \
  ";

  case $(uname -s) in
    Linux)
      CASSANDRA_CFLAGS="-Wall -pedantic -Wextra -Wno-long-long -Wno-deprecated-declarations -Wno-unused-parameter -Wno-unused-result -Wno-variadic-macros -Wno-extra-semi -pthread"
      ;;
    Darwin)
      CASSANDRA_CFLAGS="-Wall -pedantic -Wextra -Wno-long-long -Wno-deprecated-declarations -Wno-unused-parameter -Wno-unused-result -Wno-variadic-macros -Wno-extra-semi"
      ;;
  esac

  PHP_NEW_EXTENSION(cassandra,
    php_cassandra.c \
    $CASSANDRA_CLASSES \
    $CASSANDRA_EXCEPTIONS \
    $CASSANDRA_RETRY_POLICIES \
    $CASSANDRA_TYPES \
    $CASSANDRA_UTIL,
    $ext_shared, , $CASSANDRA_CFLAGS)
  PHP_ADD_BUILD_DIR($ext_builddir/src)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/Cluster)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/Exception)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/SSLOptions)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/Type)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/RetryPolicy)
  PHP_ADD_BUILD_DIR($ext_builddir/src/Cassandra/TimestampGenerator)
  PHP_ADD_BUILD_DIR($ext_builddir/util)
  PHP_SUBST(CASSANDRA_SHARED_LIBADD)
  PHP_SUBST(CASSANDRA_CFLAGS)

  ifdef([PHP_ADD_EXTENSION_DEP],
  [
    PHP_ADD_EXTENSION_DEP(cassandra, spl)
  ])

  if test "$PHP_GMP" != "no"; then
    if test -f $PHP_GMP/include/gmp.h; then
      GMP_DIR=$PHP_GMP
    fi
  else
    for i in /usr/local /usr; do
      if test -f $i/include/gmp.h; then
        GMP_DIR=$i
      fi
    done
  fi

  if test -z "$GMP_DIR"; then
    ac_extra=
  else
    ac_extra=-L$GMP_DIR/$PHP_LIBDIR
  fi

  PHP_CHECK_LIBRARY(gmp, __gmp_version,
    [
      AC_DEFINE(HAVE_GMPLIB,1,[ ])
    ],
    [
      AC_MSG_ERROR([Unable to load libgmp])
    ],
    [
      $ac_extra
    ]
  )

  if test -n "$GMP_DIR"; then
    PHP_ADD_LIBPATH($GMP_DIR/$PHP_LIBDIR, CASSANDRA_SHARED_LIBADD)
    PHP_ADD_INCLUDE($GMP_DIR/include)
  fi

  PHP_ADD_LIBRARY(gmp,, CASSANDRA_SHARED_LIBADD)

  if test "$PHP_UV" != "no"; then
    if test -f $PHP_UV/include/uv.h; then
      UV_DIR=$PHP_UV
    fi
  else
    for i in /usr/local /usr; do
      if test -f $i/include/uv.h; then
        UV_DIR=$i
      fi
    done
  fi

  if test -z "$UV_DIR"; then
    ac_extra=
  else
    ac_extra=-L$UV_DIR/$PHP_LIBDIR
  fi

  PHP_CHECK_LIBRARY(uv, uv_version,
    [
      AC_DEFINE(HAVE_UVLIB,1,[ ])
    ],
    [
      AC_MSG_ERROR([Unable to load libuv])
    ],
    [
      $ac_extra
    ]
  )

  if test -n "$UV_DIR"; then
    PHP_ADD_LIBPATH($UV_DIR/$PHP_LIBDIR, CASSANDRA_SHARED_LIBADD)
    PHP_ADD_INCLUDE($UV_DIR/include)
  fi

  PHP_ADD_LIBRARY(uv,, CASSANDRA_SHARED_LIBADD)
  PHP_ADD_LIBRARY(m,, CASSANDRA_SHARED_LIBADD)

  if test "$PHP_CASSANDRA" != "yes"; then
    if test -f $PHP_CASSANDRA/include/cassandra.h; then
      CPP_DRIVER_DIR=$PHP_CASSANDRA
    fi
  else
    for i in /usr/local /usr; do
      if test -f $i/include/cassandra.h; then
        CPP_DRIVER_DIR=$i
      fi
    done
  fi

  AC_MSG_CHECKING([for supported DataStax C/C++ driver version])
  PHP_CASSANDRA_FOUND_CASSANDRA_VERSION=`$AWK '/CASS_VERSION_MAJOR/ {printf $3"."} /CASS_VERSION_MINOR/ {printf $3"."} /CASS_VERSION_PATCH/ {printf $3}' $CPP_DRIVER_DIR/include/cassandra.h`
  PHP_CASSANDRA_FOUND_CASSANDRA_VERSION_NUMBER=`echo "${PHP_CASSANDRA_FOUND_CASSANDRA_VERSION}" | $AWK 'BEGIN { FS = "."; } { printf "%d", ([$]1 * 100 + [$]2) * 100 + [$]3;}'`
  if test "$PHP_CASSANDRA_FOUND_CASSANDRA_VERSION_NUMBER" -lt "20402"; then
  AC_MSG_ERROR([not supported. Driver version 2.4.2+ required (found $PHP_CASSANDRA_FOUND_CASSANDRA_VERSION)])
  else
    AC_MSG_RESULT([supported ($PHP_CASSANDRA_FOUND_CASSANDRA_VERSION)])
  fi

  if test -z "$CPP_DRIVER_DIR"; then
    ac_extra=
  else
    ac_extra=-L$CPP_DRIVER_DIR/$PHP_LIBDIR
  fi

  PHP_CHECK_LIBRARY(cassandra, cass_cluster_new,
    [
      AC_DEFINE(HAVE_CASSANDRALIB,1,[ ])
    ],
    [
      AC_MSG_ERROR([Unable to load libcassandra])
    ],
    [
      $ac_extra
    ]
  )

  if test -n "$CPP_DRIVER_DIR"; then
    PHP_ADD_LIBPATH($CPP_DRIVER_DIR/$PHP_LIBDIR, CASSANDRA_SHARED_LIBADD)
    PHP_ADD_INCLUDE($CPP_DRIVER_DIR/include)
  fi

  PHP_ADD_LIBRARY(cassandra,, CASSANDRA_SHARED_LIBADD)

  CASSANDRA_SHARED_LIBADD="$CASSANDRA_SHARED_LIBADD $LIBS"
fi
