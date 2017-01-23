Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty"
  config.vm.box_url = "http://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-i386-vagrant-disk1.box"
  config.vm.synced_folder ".", "/usr/local/src/php-driver"

  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus   = 2
  end

  config.vm.provision "shell", inline: <<-SHELL
  echo "Installing all necessary packages"
  sudo apt-get update
  sudo apt-get install -y g++ make cmake libuv-dev libssl-dev libgmp-dev php5 php5-dev php5-dbg openssl libpcre3-dev
  sudo apt-get install -y python-pip default-jdk
  sudo apt-get install -y git valgrind

  sudo su - vagrant

  sudo pip install ccm

  rm -Rf /tmp/php-driver-installation/
  mkdir /tmp/php-driver-installation
  pushd /tmp/php-driver-installation

  mkdir build
  builddir=$(cd build; pwd)

  echo "Compiling cpp-driver..."
  mkdir cpp-driver
  pushd cpp-driver
  cmake -DCMAKE_CXX_FLAGS="-fPIC" -DCMAKE_INSTALL_PREFIX:PATH=$builddir -DCASS_BUILD_STATIC=ON -DCASS_BUILD_SHARED=OFF -DCMAKE_BUILD_TYPE=RELEASE -DCMAKE_INSTALL_LIBDIR:PATH=lib -DCASS_USE_ZLIB=ON /usr/local/src/php-driver/lib/cpp-driver
  make
  make install
  popd
  rm -Rf cpp-driver

  mv build/lib/libcassandra_static.a build/lib/libcassandra.a
  rm build/lib/libcassandra.so*
  popd

  echo "Compiling and installing the extension..."
  pushd /usr/local/src/php-driver/ext
  phpize
  LDFLAGS="-L$builddir/lib" LIBS="-lssl -lz -luv -lm -lgmp -lstdc++" ./configure --with-cassandra=/tmp/php-driver-installation/build --with-gmp=/tmp/php-driver-installation/build --with-libdir=lib
  make
  sudo make install
  sudo sh -c 'echo "extension=cassandra.so" > /etc/php5/cli/conf.d/100-cassandra.ini'
  popd
  rm -Rf /tmp/php-driver-installation/

  echo "Installing composer and php-driver dependencies..."
  pushd /usr/local/src/php-driver/
  curl -sS https://getcomposer.org/installer | php
  php composer.phar install
  popd
  SHELL
end
