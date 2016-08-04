Contributing
============

Submitting Your Work
--------------------

All work in the repository is released under the MIT and Apache 2.0 licenses. For your submission to be considered for inclusion, please include the following statement in each of your submissions. (A simple copy and paste will do.)

> By making a contribution to this project, I certify that:
>
> (a) The contribution was created in whole or in part by me and I have the right to submit it under the MIT and Apache 2.0 licenses; or
>
> (b) The contribution is based upon previous work that, to the best of my knowledge, is covered under an appropriate open source license and I have the right under that license to submit that work with modifications, whether created in whole or in part by me, under the MIT or Apache 2.0 licenses; or
>
> (c) The contribution was provided directly to me by some other person who certified (a), (b) or (c) and I have not modified it.
>
> (d) I understand and agree that this project and the contribution are public and that a record of the contribution (including all personal information I submit with it) is maintained indefinitely and may be redistributed consistent with this project or the open source license(s) involved.

If the statement is not included your submission will not be included in any release of Sqon. You will likely be notified that the statement is missing and you will have time to correct that mistake.

Style Guide
-----------

Code submissions must follow the [PSR-2][] coding style guide.

[PSR-2]: http://www.php-fig.org/psr/psr-2/

Issues
------

When creating a new issue, please provide as much detail as you can about the issue in the description. If the issue is a bug, please provide a minimal code sample that will replicate the issue for everyone else. Please be sure to also specify the version or commit number of the library that you are using. The title of the issue should be a summary of the description that includes the subject (e.g. "The bootstrap script") and problem (e.g. "does not have a shebang line.").

If you find that you are affected by an open issue, please use GitHub's reactions to share how it affects. Using `+1` indicates that the issue also impacts you, `-1` if you feel that the issue will have a negative impact on you, and `Heart` if the issue is something on your wishlist. Posting a comment to the same effect will be deleted. Please only post comments if you are able to provide additional relevant information.

Pull Requests
-------------

Please use the following table as a template for creating the body text for your pull request.

<table>
  <tbody>
    <tr>
      <th>Type</th>
      <td>Bug Fix</th>
    </tr>
    <tr>
      <th>Issue</th>
      <td>#123</th>
    </tr>
    <tr>
      <th>Change</th>
      <td>Patch</th>
    </tr>
  </tbody>
</table>

- The **Type** field must be one of the following values:
    - Bug Fix
    - Enhancement
    - Feature
    - Optimization
    - Security
- The **Issue** field may reference open issues that may be resolved by the pull request.
- The **Change** field must be one of the following values:
    - Major - indicates that it is a backwards incompatible change.
    - Minor - indicates that it adds a new backwards compatible feature, enhancement, or optimization.
    - Patch - indicates that it is a backwards compatible bug fix.


All pull requests are required to have passing tests. New features and changes to existing features will require documentation changes to be included in the PR.

Version Support
---------------

When submitting code, please take into consideration the current version of the PHP or HHVM interpreter supported by the Sqon project. Sqon will always target support for current LTS releases of PHP and HHVM. The [Requirements][] section of the README has more information.

[Requirements]: README.md#requirements