{
  description = "cohete-framework - Async PHP framework on ReactPHP";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
  };

  outputs = { self, nixpkgs }:
    let
      systems = [ "x86_64-linux" "aarch64-darwin" ];
      forAllSystems = nixpkgs.lib.genAttrs systems;
      pkgsFor = system: import nixpkgs { inherit system; };

      # Extensions already in `enabled` by default: ctype, curl, dom, fileinfo,
      # filter, iconv, mbstring, mysqlnd, openssl, pdo, session, simplexml,
      # tokenizer, xml, xmlreader, xmlwriter, zlib.
      # We only add what is NOT there by default.
      extraExtensions = { all }: with all; [
        pcntl
        posix
        sockets
        intl
        pdo_mysql
        event       # libevent loop for ReactPHP (pecl/ev not in nixpkgs)
        xdebug
      ];

      phpConfig = ''
        memory_limit = 512M
        xdebug.mode = off
      '';
    in
    {
      devShells = forAllSystems (system:
        let
          pkgs = pkgsFor system;

          php = pkgs.php82.buildEnv {
            extensions = { enabled, all }: enabled ++ (extraExtensions { inherit all; });
            extraConfig = phpConfig;
          };
        in
        {
          default = pkgs.mkShell {
            buildInputs = [
              php
              php.packages.composer
            ];

            shellHook = ''
              echo "cohete-framework dev environment"
              echo "  PHP:      $(php -v | head -1)"
              echo "  Composer: $(composer --version 2>/dev/null | head -1)"
              echo ""
              echo "Commands:"
              echo "  composer install"
              echo "  vendor/bin/phpunit"
              echo "  vendor/bin/phpstan analyse"
            '';
          };
        });

      checks = forAllSystems (system:
        let
          pkgs = pkgsFor system;

          php = pkgs.php82.buildEnv {
            extensions = { enabled, all }: enabled ++ (extraExtensions { inherit all; });
            extraConfig = "memory_limit = 512M";
          };
        in
        {
          tests = pkgs.runCommand "cohete-framework-tests" {
            src = self;
            nativeBuildInputs = [ php php.packages.composer pkgs.git ];
            COMPOSER_HOME = "/tmp/composer-home";
            COMPOSER_CACHE_DIR = "/tmp/composer-cache";
          } ''
            cp -r $src source
            chmod -R u+w source
            cd source

            export HOME=/tmp

            composer install --no-interaction --no-progress --prefer-dist
            vendor/bin/phpunit --no-coverage
            vendor/bin/phpstan analyse --no-progress

            touch $out
          '';
        });
    };
}
