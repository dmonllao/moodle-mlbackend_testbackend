"""Utility module to delete a model directory"""

from __future__ import print_function
import os
import sys
import shutil

def is_model_version_dir(versiondir):
    """Check that this directory contents belong to a model version"""

    # Check all children they can only be 'execution', 'evaluation'
    # or 'testing'.
    for processname in os.listdir(versiondir):

        if os.path.isdir(os.path.join(versiondir, processname)) is False:
            return False

        if (processname != 'evaluation' and processname != 'execution' and
                processname != 'testing'):
            return False

    return True

def is_model_dir(modeldir):
    """Check that this directory contents belong to a model"""

    # Direct childs are the model versions (timestamps).
    for modelversion in os.listdir(modeldir):

        if os.path.isdir(os.path.join(modeldir, modelversion)) is False:
            # Not a single non dir file.
            return False

        # Using 1 Jan 2017 here, but it could be the release date.
        if (str.isdigit(modelversion) is False or len(modelversion) > 10 or
                int(modelversion) < 1483228800):
            return False

        versiondir = os.path.join(modeldir, modelversion)
        if is_model_version_dir(versiondir) is False:
            return False

    return True

def delete_dir():
    """Deletes the provided directory"""

    if len(sys.argv) < 2:
        print('Missing directory argument')
        sys.exit(1)

    dirpath = sys.argv[1]

    # We can not just delete whatever directory we receive, we need
    # to check (as much as we can) that the directory comes from
    # Moodle so we need to perform some extra checkings before deleting
    # the file.

    if os.path.exists(dirpath) is False:
        # Ok, no worries, it may not exist.
        sys.exit(0)

    # This function can delete both the whole model directory
    # and a specific model version directory; we will later check
    # that the received dirpath belongs to a model but we first
    # need to see if we are dealing with a model directory (contains
    # multiple versions) or a specific model version."""
    modeldir = False
    for subdirname in os.listdir(dirpath):
        if (subdirname != 'evaluation' and subdirname != 'execution' and
                subdirname != 'testing'):
            # Model version directories can only contain these 3 subdirectories.
            modeldir = True

    if modeldir:
        if is_model_dir(dirpath):
            shutil.rmtree(dirpath)
    else:
        if is_model_version_dir(dirpath):
            shutil.rmtree(dirpath)

delete_dir()
