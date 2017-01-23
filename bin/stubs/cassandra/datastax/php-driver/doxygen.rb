#!/usr/bin/env ruby

file = ARGV[0]

unless file
  warn "Usage doxygen.rb FILE"
  exit 1
end

text = File.read(file)

# short array syntax
text.gsub!(/((var|public|protected|private)(\s+static)?)\s+(\$[^\s;=]+)\s+\=\s+\[([\s\S]*?)\]\;/, '\1 \4 = array(\5);')

# class attribute typehints
text.gsub!(/\@(var|type)\s+([^\s]+)([^\/]+)\/\s+((var|public|protected|private)(\s+static)?)\s+(\$[^\s;=]+)/, '\3/ \4 \2 \7')

# fix backslashes in docblocks
while text.gsub!(/(\s+\*.*?)(\s)\\([A-Z][a-zA-Z0-9_]*)/, '\1\2\3')
end
while text.gsub!(/(\s+\*.*?)([A-Z][a-zA-Z0-9_]*)\\/, '\1\2::')
end

# method return typehints
text.gsub!(/(\/\*\*[\s\S]*?@return\s+([^\s]*)[\s\S]*?\*\/[\s\S]*?)((public|protected|private)(\s+static)?)?\s+function\s+([\S]*?)\s*?\(/, '\1 \3 \2 function \6(')
text.gsub!(/\@return/, '@retval')

puts text
