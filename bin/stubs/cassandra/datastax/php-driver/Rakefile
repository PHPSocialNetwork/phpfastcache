require 'erb'

class Release
  def initialize(version)
    parts   = version.split('.', 3).map(&:to_i)
    major   = parts[0] || 0
    minor   = parts[1] || 0
    release = parts[2] || 0

    parts     = version.split('-', 2)
    stability = parts[1] || 'stable'
    stability = 'devel'  if major < 1
    # strip release modifier e.g. beta.1 becomes beta
    stability, number = stability.split('.', 2)

    pecl_stability = nil
    pecl_number    = nil

    pecl_number    = number unless number.nil?
    pecl_stability = stability if stability != 'stable'
    pecl_stability = pecl_stability.upcase if pecl_stability == 'rc'
    pecl_stability = 'dev' if pecl_stability == 'devel'

    @version      = version
    @major        = major
    @minor        = minor
    @release      = release
    @stability    = stability
    @pecl_version = "#{major}.#{minor}.#{release}"
    @pecl_version = "#{major}.#{minor}.#{release}-#{pecl_stability}#{pecl_number}" if stability != 'stable'
    @dirname      = File.expand_path(File.dirname(__FILE__))
  end

  def perform!
    puts "Preparing to release PHP Driver v#{version}-#{@stability}..."
    bump_version
    tag_repository
  end

  private

  attr_reader :version, :major, :minor, :release, :stability, :pecl_version

  def timestamp
    Time.now
  end

  def sources
    (
      Dir.glob(@dirname + '/ext/config.{m4,w32}') +
      Dir.glob(@dirname + '/ext/php_cassandra.{c,h}') +
      Dir.glob(@dirname + '/ext/{php_cassandra_types.h,version.h}') +
      Dir.glob(@dirname + '/ext/src/**/*.{c,h}') +
      Dir.glob(@dirname + '/ext/util/**/*.{c,h}')
    ).map {|p| p.gsub(@dirname + '/ext/', '') }.sort
  end

  def docs
    (
      Dir.glob(@dirname + '/ext/doc/**/*.*') <<
      File.join(@dirname, 'ext/LICENSE')
    ).map {|p| p.gsub(@dirname + '/ext/', '') }.sort
  end

  def tests
    Dir.glob(@dirname + '/ext/tests/**/*.phpt').
      map {|p| p.gsub(@dirname + '/ext/', '') }.sort
  end

  def files
    {
      'src'  => sources,
      'doc'  => docs,
      'test' => tests
    }
  end

  def notes
    notes = ''
    state = :start

    File.read(@dirname + '/CHANGELOG.md').each_line do |line|
      case state
      when :start
        next unless line.start_with?("# ")

        if @version == line[2..-1].strip
          notes << line
          state = :body
        end
      when :body
        break if line.start_with?("# ")
        notes << line
      end
    end

    if @stability == 'stable' && notes == ''
      raise ::RuntimeError,
            %[#{@dirname}/CHANGELOG.md Does not Contain Information for Release: ] +
            %[Missing information for v#{@version}]
    end
    notes = '# Official release under development' if notes == ''
    notes.strip
  end

  def package_xml
    ERB.new(<<-ERB)
<?xml version="1.0" encoding="UTF-8"?>
<package version="2.1" xmlns="http://pear.php.net/dtd/package-2.1" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0     http://pear.php.net/dtd/tasks-1.0.xsd     http://pear.php.net/dtd/package-2.1     http://pear.php.net/dtd/package-2.1.xsd">
  <name>cassandra</name>
  <channel>pecl.php.net</channel>
  <summary>DataStax PHP Driver for Apache Cassandra</summary>
  <description>
A modern, feature-rich and highly tunable PHP client library for Apache
Cassandra and DataStax Enterprise using exclusively Cassandra's binary
protocol and Cassandra Query Language v3.
  </description>
  <lead>
    <name>Michael Penick</name>
    <user>mpenick</user>
    <email>michael.penick@datastax.com</email>
    <active>yes</active>
  </lead>
  <date><%= timestamp.strftime('%Y-%m-%d') %></date>
  <time><%= timestamp.strftime('%H:%M:%S') %></time>
  <version>
    <release><%= pecl_version %></release>
    <api><%= pecl_version %></api>
  </version>
  <stability>
    <release><%= release_stability %></release>
    <api><%= api_stability %></api>
  </stability>
  <license uri="http://www.apache.org/licenses/LICENSE-2.0">Apache License 2.0</license>
  <notes>
<%= notes %>
  </notes>
  <contents>
    <dir name="/"><%
files.each do |role, list|
  list.each do |file|
%>
      <file role="<%= role %>" name="<%= file %>" /><%
  end
end
%>
    </dir>
  </contents>
  <dependencies>
  <required>
   <php>
    <min>5.5.0</min>
    <max>7.0.99</max>
   </php>
   <pearinstaller>
    <min>1.4.8</min>
   </pearinstaller>
  </required>
  </dependencies>
  <providesextension>cassandra</providesextension>
  <extsrcrelease/>
</package>
    ERB
  end

  def version_h
    ERB.new(<<-ERB)
#ifndef PHP_CASSANDRA_VERSION_H
#define PHP_CASSANDRA_VERSION_H

/* Define Extension and Version Properties */
#define PHP_CASSANDRA_NAME         "cassandra"
#define PHP_CASSANDRA_MAJOR        <%= major %>
#define PHP_CASSANDRA_MINOR        <%= minor %>
#define PHP_CASSANDRA_RELEASE      <%= release %>
#define PHP_CASSANDRA_STABILITY    "<%= stability %>"
#define PHP_CASSANDRA_VERSION      "<%= pecl_version %>"
#define PHP_CASSANDRA_VERSION_FULL "<%= version %>"

#endif /* PHP_CASSANDRA_VERSION_H */
    ERB
  end

  def create_package_xml
    if @stability.start_with?('rc')
      api_stability     = 'stable'
      release_stability = 'beta'
    else
      api_stability = release_stability = @stability
    end

    [api_stability, release_stability].each do |stability|
      unless ["snapshot", "devel", "alpha", "beta", "stable"].include?(stability)
        raise ::ArgumentError,
              %[stability must be "snapshot", "devel", "alpha", "beta" or ] +
              %["stable", #{stability.inspect} given]
      end
    end

    File.open(@dirname + '/ext/package.xml', 'w+') do |f|
      f.write(package_xml.result(binding))
    end
  end

  def create_version_h
    File.open(@dirname + '/ext/version.h', 'w+') do |f|
      f.write(version_h.result(binding))
    end
  end

  def bump_version
    puts "Bumping version.h and package.xml"
    create_version_h
    create_package_xml
    # system('git', 'add', @dirname + '/ext/package.xml')
    # system('git', 'add', @dirname + '/ext/version.h')
    # system('git', 'commit', '-m', "prepare release v#{@version}")
  end

  def tag_repository
    puts "Creating v#{version} tag"
    # system('git', 'tag', "v#{@version}")
    # system('git', 'push')
  end
end

desc 'Prepare a new release of the PHP Driver'
task :release, [:version] do |t, args|
  Release.new(args['version']).perform!
end
