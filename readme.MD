
# Tier - a layered application architecture.

An application runner framework that uses a DIC to allow multiple 'tiers', to allow complete separation between routing, controllers and view tiers.

[![Build Status](https://travis-ci.org/Danack/Tier.svg?branch=master)](https://travis-ci.org/danack/tier)

The real name for the "pattern" is breaking the execution of a program up into individual blocks. Each of those blocks can have their own set of dependencies.

This is why the 'framework' is called Tier, as it allows you to "arrange in layers" . Each block of execution builds up information to be used by subsequent execution blocks.

# Documentation 

The documentation, such as it is, is available at http://tier.phpjig.com/

# How to run

There is a skeleton application available [here](https://github.com/Danack/TierJigSkeleton), which can be run with:

```
git clone https://github.com/Danack/TierJigSkeleton
cd TierJigSkeleton/
composer install
php -S localhost:8000 -t public
```

# Running built in test


php -S localhost:8000 -t ./test/app/public
